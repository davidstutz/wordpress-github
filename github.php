<?php
/**
 * Plugin Name: Wordpress GitHub
 * Description: GitHub plugin for Wordpress.
 * Version: 0.9
 * Author: David Stutz
 * Author URI: http://davidstutz.de
 * License: GPL 2
 */
/**
 * Copyright (C) 2015  David Stutz
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

if (!class_exists('GitHubClient')) {
    require_once __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR
            . 'github-php-client' . DIRECTORY_SEPARATOR . 'client'
            . DIRECTORY_SEPARATOR . 'GitHubClient.php';
}

/**
 * Main class of the pluign.
 */
class Github {
    
    /**
     * Seconds to cache result.
     */
    const CACHE_EXPIRE = 43200;
    
    /**
     * Templates for some of the shortcodes.
     * 
     * @var type 
     */
    protected static $templates = array(
        'default' => array(
            'github_commits' => '<li class="wp-github-commit"><b><a href="https://github.com/:repository" target="_blank">:repository</a>@<a href=":url" target="_blank"><code class="wp-github-commit-sha">:sha</code></a></b>: :message <a href=":user_url" target="_blank"><img style="display:inline;" height="20" width="20" class="wp-github-commit-avatar" src=":user_avatar" /> :user_login</a>, :date</li>',
            'github_issues' => '<li class="wp-github-issue"><b><a href="https://github.com/:repository" target="_blank">:repository</a>#<a href=":url" target="_blank"><code class="wp-github-issue-number">:number</code></a></b>: :title <a href=":user_url" target="_blank"><img style="display:inline;" height="20" width="20" class="wp-github-commit-avatar" src=":user_avatar" /> :user_login</a>, :state, :date</li>',
            'github_releases' => '<li class="wp-github-release"><b><a href="https://github.com/:repository" target="_blank">:repository</a>/<a href=":url">:tag_name</a></b>: :name <a href=":tar_url" class="wp-github-release-tar">tar</a>, <a href=":zip_url" class="wp-github-release-zip">zip</a>, :date</li>',
        ),
    );
    
    /**
     * Initialize all provided shortcodes.
     */
    public static function init() {
        
        // Add settings menu.
        add_action('admin_menu', array('Github', 'admin_menu'));
        add_action('admin_init', array('Github', 'register_settings'));
        
        add_shortcode('github-commits', array('Github', 'commits'));
        add_shortcode('github-issues', array('Github', 'issues'));
        add_shortcode('github-releases', array('Github', 'releases'));
        add_shortcode('github-user-num-repos', array('Github', 'user_num_repos'));
    }
    
    /**
     * Get a GitHub client object.
     * 
     * @return \GitHub\Client
     */
    public static function setup_client() {
        $client = new GitHubClient();
        
        $login = get_option('wp_github_login', FALSE);
        $password = get_option('wp_github_password', FALSE);
        
        if ($login !== FALSE && $password !== FALSE) {
            $client->setAuthType(GitHubClient::GITHUB_AUTH_TYPE_BASIC);
            $client->setCredentials($login, $password);
        }
        
        return $client;
    }
    
    public static function md5($input) {
        return md5(serialize($input));
    }
    
    /**
     * Set a value in the cache.
     */
    public static function cache_set($key, $data, $expire) {
        wp_cache_set($key, $data, '', 0);
    }
    
    /**
     * Get a value from the cache.
     */
    public static function cache_get($key) {
        return wp_cache_get($key);
    }
    
    /**
     * Add the admin menu for authentication. Will add a "GitHub" menu item in "Settings".
     */
    public static function admin_menu() {
        add_options_page('WP GitHub Options', 'GitHub', 'manage_options', 'wp-github-options', array('Github', 'plugin_options'));
    }
    
    /**
     * The plugin' settings page.
     */
    public static function plugin_options() {
        if (!current_user_can('manage_options'))  {
            wp_die(__( 'You do not have sufficient permissions to access this page.', 'wp-github'));
	}
        
        Github::check_credentials();
        
	echo '<div class="wrap">';
	echo '<h2>' . __('WP GitHub Options', 'wp-github') . '</h2>';
        echo '<form method="POST" action="options.php"> ';
        echo settings_fields('wp_github');
        echo do_settings_sections('wp-github-options');
        echo submit_button();
        echo '</form>';
	echo '</div>';
    }
    
    /**
     * Register settings for authentication: login and password.
     */
    public static function register_settings() {
        register_setting(
            'wp_github',
            'wp_github_login'
        );

        register_setting(
            'wp_github',
            'wp_github_password'
        );
        
        add_settings_section(
            'wp_github_section',
            'Authentication',
            array('Github', 'settings_info'), 
            'wp-github-options'
        );  

        add_settings_field(
            'login',
            'Login',
            array('Github', 'settings_login'), 
            'wp-github-options',
            'wp_github_section'          
        );      

        add_settings_field(
            'password', 
            'Password', 
            array('Github', 'settings_password'), 
            'wp-github-options', 
            'wp_github_section'
        );
    }
    
    /**
     * Info for the authentication settings group.
     */
    public static function settings_info() {
        echo 'In order of the plugin to successfully query the GitHub API, '
                . 'the following credentials are necessary for authentication:';
    }
    
    /**
     * Settings login field.
     */
    public static function settings_login() {
        echo '<input type="text" name="wp_github_login" value="' . get_option('wp_github_login') . '" />';
    }
    
    /**
     * Settings password field.
     */
    public static function settings_password() {
        echo '<input type="password" name="wp_github_password" value="' . get_option('wp_github_password') . '" />';
    }
    
    /**
     * Check the credentials and see if user is valid and check rate limit.
     */
    public static function check_credentials() {
        $login = get_option('wp_github_login', FALSE);
        $password = get_option('wp_github_password', FALSE);
        
        $client = Github::setup_client();
        
        $client->setAuthType(GitHubClient::GITHUB_AUTH_TYPE_BASIC);
        $client->setCredentials($login, $password);
        
        $user = $client->users->getSingleUser($login);
        
        $rate_limit = $client->getRateLimit();
        $rem_rate_limit = $client->getRateLimitRemaining();
        
        echo '<div class="updated"><p>Authentation with login <b>' . $login . '</b> (' . $user->getName() . ', ' . $user->getPublicRepos() . ' public repositories): rate limit: ' . ($rate_limit - $rem_rate_limit) . ' / ' . $rate_limit . '</p></div>';
    }
    
    /**
     * Display commits of the given repositories:
     * 
     *  [github-commits repositories="davidstutz/wordpress-github,davidstutz/wordpress-user-biography" limit="5" format="m/d/Y"]
     * 
     * @param string $content
     * @param array $attributes
     * @return string
     */
    public static function commits($attributes, $content = null) {
        extract(shortcode_atts(array(
            'repositories' => '',
            'limit' => 5,
            'format' => 'm/d/Y',
            'template' => 'default',
        ), $attributes));
        
        $client = Github::setup_client();
        
        $cache_string = 'wp_github_commits' . Github::md5($attributes);
        $cache = Github::cache_get($cache_string);
        if ($cache !== FALSE) {
            return $cache;
        }
        
        $commits = array();
        if (!empty($repositories)) {
            $repositories = explode(',', $repositories);
            
            foreach ($repositories as $repository) {
                $tmp = explode('/', $repository);
                
                if (sizeof($tmp) != 2) {
                    continue;
                }
                
                $user = $tmp[0];
                $repo = $tmp[1];
                
                $repo_commits = $client->repos->commits->listCommitsOnRepository($user, $repo);
                
                foreach ($repo_commits as $commit) {
                    $commits[] = array(
                        ':repository' => $repository,
                        ':timestamp' => strtotime($commit->getCommit()->getAuthor()->date),
                        ':date' => date($format, strtotime($commit->getCommit()->getAuthor()->date)),
                        ':message' => $commit->getCommit()->getMessage(),
                        ':url' => $commit->getCommit()->getUrl(),
                        ':user_avatar' => $commit->getAuthor()->getAvatarUrl(),
                        ':user_login' => $commit->getAuthor()->getLogin(),
                        ':user_url' => $commit->getAuthor()->getUrl(),
                        ':sha' => substr($commit->getSha(), 0, 7),
                    );
                }
            }
        }
        
        if (!function_exists('sort_commits')) {
            /**
             * Sorts commits by time (recent first).
             * 
             * @return string
             */
            function sort_commits($a, $b) {
                if ($a[':timestamp'] == $b[':timestamp']) {
                    return 0;
                }

                return $a[':timestamp'] < $b[':timestamp'] ? 1 : -1;
            }
        }
        
        usort($commits, 'sort_commits');
        
        $count = 1;
        $html = '<ul class="wp-github-commits">';
        foreach ($commits as $commit) {
            if ($count > $limit) {
                break;
            }
            
            $html .= strtr(Github::$templates[$template]['github_commits'], $commit);
            $count++;
        }
        $html .= '</ul>';
        
        Github::cache_set($cache_string, $html, Github::CACHE_EXPIRE);
        
        return $html;
    }
    
    /**
     * List issues of the given repositories:
     * 
     *  [github-issues repositories="davidstutz/wordpress-github,davidstutz/wordpress-user-biography" limit="5" format="m/d/Y"]
     * 
     * @param array $attributes
     * @param string $content
     * @return string
     */
    public static function issues($attributes, $content = null) {
        extract(shortcode_atts(array(
            'repositories' => '',
            'limit' => 5,
            'format' => 'm/d/Y',
            'template' => 'default',
        ), $attributes));
        
        $client = Github::setup_client();
        
        $cache_string = 'wp_github_issues' . Github::md5($attributes);
        $cache = Github::cache_get($cache_string);
        if ($cache !== FALSE) {
            return $cache;
        }
        
        $issues = array();
        if (!empty($repositories)) {
            $repositories = explode(',', $repositories);
            
            foreach ($repositories as $repository) {
                $tmp = explode('/', $repository);
                
                if (sizeof($tmp) != 2) {
                    continue;
                }
                
                $user = $tmp[0];
                $repo = $tmp[1];
                
                $repo_issues = $client->issues->listIssues($user, $repo);
                
                foreach ($repo_issues as $issue) {
                    $issues[] = array(
                        ':repository' => $repository,
                        ':timestamp' => strtotime($issue->getCreatedAt()),
                        ':date' => date($format, strtotime($issue->getCreatedAt())),
                        ':url' => $issue->getUrl(),
                        ':number' => $issue->getNumber(),
                        ':title' => $issue->getTitle(),
                        ':state' => $issue->getState(),
                        ':user_avatar' => $issue->getUser()->getAvatarUrl(),
                        ':user_url' => $issue->getUser()->getUrl(),
                        ':user_login' => $issue->getUser()->getLogin(),
                        ':comments' => $issue->getComments(),
                    );
                }
            }
        }
        
        if (!function_exists('sort_issues')) {
            /**
             * Sorts commits by time (recent first).
             * 
             * @return string
             */
            function sort_issues($a, $b) {
                if ($a[':timestamp'] == $b[':timestamp']) {
                    return 0;
                }

                return $a[':timestamp'] < $b[':timestamp'] ? 1 : -1;
            }
        }
        
        usort($issues, 'sort_issues');
        
        $count = 1;
        $html = '<ul class="wp-github-issues">';
        foreach ($issues as $issue) {
            if ($count > $limit) {
                break;
            }
            
            $html .= strtr(Github::$templates[$template]['github_issues'], $issue);
            $count++;
        }
        $html .= '</ul>';
        
        Github::cache_set($cache_string, $html, Github::CACHE_EXPIRE);
        
        return $html;
    }
    
    public static function releases($attributes, $content = null) {
        extract(shortcode_atts(array(
            'repositories' => '',
            'format' => 'm/d/Y',
            'limit' => 3,
            'template' => 'default',
        ), $attributes));
        
        $client = Github::setup_client();
        
        $cache_string = 'wp_github_releases' . Github::md5($attributes);
        $cache = Github::cache_get($cache_string);
//        if ($cache !== FALSE) {
//            return $cache;
//        }
        
        $releases = array();
        if (!empty($repositories)) {
            $repositories = explode(',', $repositories);
            
            foreach ($repositories as $repository) {
                $tmp = explode('/', $repository);
                
                if (sizeof($tmp) != 2) {
                    continue;
                }
                
                $user = $tmp[0];
                $repo = $tmp[1];
                
                $repo_releases = $client->repos->releases->listReposReleases($user, $repo);
                
                foreach ($repo_releases as $release) {
                    $releases[] = array(
                        ':repository' => $repository,
                        ':timestamp' => strtotime($release->getPublished_at()),
                        ':date' => date($format, strtotime($release->getPublished_at())),
                        ':name' => $release->getName(),
                        ':url' => $release->getUrl(),
                        ':tag_name' => $release->getTag_name(),
                        ':tar_url' => $release->getTarball_url(),
                        ':zip_url' => $release->getZipball_url(),
                    );
                }
            }
        }
        
        if (!function_exists('sort_releases')) {
            /**
             * Sorts commits by time (recent first).
             * 
             * @return string
             */
            function sort_releases($a, $b) {
                if ($a[':timestamp'] == $b[':timestamp']) {
                    return 0;
                }

                return $a[':timestamp'] < $b[':timestamp'] ? 1 : -1;
            }
        }
        
        usort($releases, 'sort_releases');
        
        $count = 1;
        $html = '<ul class="wp-github-releases">';
        foreach ($releases as $release) {
            if ($count > $limit) {
                break;
            }
            
            $html .= strtr(Github::$templates[$template]['github_releases'], $release);
            $count++;
        }
        $html .= '</ul>';
        
        Github::cache_set($cache_string, $html, Github::CACHE_EXPIRE);
        
        return $html;
    }
    
    /**
     * Get the number of repositories of the user:
     * 
     *  [github-user-num-repos user="davidstutz"]
     * 
     * @param array $attributes
     * @param string $content
     * @return string
     */
    public static function user_num_repos($attributes, $content = null) {
        extract(shortcode_atts(array(
            'user' => '',
            'type' => 'owner',
        ), $attributes));
        
        $client = Github::setup_client();
        
        $cache_string = 'wp_github_user_num_repos' . Github::md5($attributes);
        $cache = Github::cache_get($cache_string);
        if ($cache !== FALSE) {
            return $cache;
        }
        
        if (!empty($user)) {
           
            $num = $client->users->getSingleUser($user)->getPublicRepos();
            Github::cache_set($cache_string, $num, Github::CACHE_EXPIRE);
            
            return $num;
        }
        
        return '';
    }
}

Github::init();
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
    
    protected static $templates = array(
        'default' => array(
            'github_commits' => '<li class="wp-github-commit"><b><a href="https://github.com/:repository" target="_blank">:repository</a>@<a href=":url" target="_blank"><code class="wp-github-commit-sha">:sha</code></a></b>: :message <a href=":user_url" target="_blank"><img style="display:inline;" height="20" width="20" class="wp-github-commit-avatar" src=":user_avatar" /> :user_login</a>, :date</li>',
            'github_issues' => '<li class="wp-github-issue"><b><a href="https://github.com/:repository" target="_blank">:repository</a>@<a href=":url" target="_blank"><code class="wp-github-issue-number">:number</code></a></b>: :title <a href=":user_url" target="_blank"><img style="display:inline;" height="20" width="20" class="wp-github-commit-avatar" src=":user_avatar" /> :user_login</a>, :state, :date</li>',
        ),
    );
    
    /**
     * Initialize all provided shortcodes.
     */
    public static function init_shortcodes() {
        add_shortcode('github-commits', array('Github', 'commits'));
        add_shortcode('github-issues', array('Github', 'issues'));
        add_shortcode('github-pull-requests', array('Github', 'pull_requests'));
        add_shortcode('github-user-num-repos', array('Github', 'user_num_repos'));
        add_shortcode('github-user-num-forks', array('Github', 'user_num_forks'));
        add_shortcode('github-user-num-commits', array('Github', 'user_num_commits'));
        add_shortcode('github-user-num-stars', array('Github', 'user_num_stars'));
        add_shortcode('github-user-num-starred', array('Github', 'user_num_starred'));
        add_shortcode('github-user-num-following', array('Github', 'user_num_following'));
        add_shortcode('github-user-num-followers', array('Github', 'user_num_followers'));
        add_shortcode('github-user-num-contributors', array('Github', 'user_num_contributors'));
        add_shortcode('github-user-num-pull-requests', array('Github', 'user_num_pull_requests')); // Pull request to other public repos.
        add_shortcode('github-user-num-contributing-repos', array('Github', 'user_num_contributing_repos')); // Number of public repositores contributed to.
        add_shortcode('github-user-num-contributions', array('Github', 'user_num_contributions'));
    }
    
    /**
     * Get a GitHub client object.
     * 
     * @return \GitHub\Client
     */
    public static function setup_client() {
        return new GitHubClient();
    }
    
    /**
     * Shortcode to display commits of a number of repositories on GitHub.
     * 
     * @param type $content
     * @param type $attributes
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
        
        if (!empty($repositories)) {
            $repositories = explode(',', $repositories);
            
            $commits = array();
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
        
        return $html . '</ul>';
    }
    
    public static function issues($attributes, $content = null) {
        extract(shortcode_atts(array(
            'repositories' => '',
            'limit' => 5,
            'format' => 'm/d/Y',
            'template' => 'default',
        ), $attributes));
        
        $client = Github::setup_client();
        
        if (!empty($repositories)) {
            $repositories = explode(',', $repositories);
            
            $issues = array();
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
        
        return $html . '</ul>';
    }
}

Github::init_shortcodes();
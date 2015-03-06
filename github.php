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
     * Initialize all provided shortcodes.
     */
    public static function init_shortcodes() {
        add_shortcode('github-commits', array('Github', 'commits'));
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
                        'repository' => $repository,
                        'timestamp' => strtotime($commit->getCommit()->getAuthor()->date),
                        'commit' => $commit,
                    );
                }
            }
            
            //return '<pre>' . print_r($commits, TRUE) . '</pre>';
        }
        
        if (!function_exists('sort_commits')) {
            /**
             * Sorts commits by time (recent first).
             * 
             * @return string
             */
            function sort_commits($a, $b) {
                if ($a['timestamp'] == $b['timestamp']) {
                    return 0;
                }

                return $a['timestamp'] < $b['timestamp'] ? 1 : -1;
            }
        }
        
        usort($commits, 'sort_commits');
        
        $count = 1;
        $html = '<ul class="wp-github-commits">';
        foreach ($commits as $commit) {
            if ($count > $limit) {
                break;
            }
            
            $html .= '<li class="wp-github-commit"><b>' . $commit['repository']
                    . '</b> - ' . date('Y-m-d', $commit['timestamp']) . ': '
                    . $commit['commit']->getCommit()->getMessage() . '</li>';
            
            $count++;
        }
        
        return $html . '</ul>';
    }
    
    
}

Github::init_shortcodes();
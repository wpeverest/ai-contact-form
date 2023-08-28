<?php

namespace EverestForms\AI;

class GithubUpdater {

    private $file;
    private $basename;
    private $active;
    private $username;
    private $repository;
    private $github_response;

    public function __construct($file) {
        $this->file = $file;
        $this->basename = plugin_basename($file);
        add_action('admin_init', array($this, 'initialize'));
    }

    public function set_username($username) {
        $this->username = $username;
    }

    public function set_repository($repository) {
        $this->repository = $repository;
    }

    public static function setup() {
        $updater = new self(EVF_AI_PLUGIN_FILE);
        $updater->set_username('wpeverest');
        $updater->set_repository('ai-contact-form');
        $updater->initialize();
    }

    private function get_repository_info() {
        if (is_null($this->github_response)) {
            $request_uri = sprintf(
                'https://api.github.com/repos/%s/%s/releases',
                $this->username,
                $this->repository
            );

            $response = wp_remote_get($request_uri);

            if (is_wp_error($response)) {
                return;
            }

            $body = wp_remote_retrieve_body($response);
            $this->github_response = json_decode($body, true);

            if (is_array($this->github_response)) {
                $this->github_response = current($this->github_response);
            }
        }
    }

    public function initialize() {
        add_filter('pre_set_site_transient_update_plugins', array($this, 'modify_transient'), 10, 1);
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
    }

    public function modify_transient($transient) {
        if (property_exists($transient, 'checked')) {
            if ($checked = $transient->checked) {
                $this->get_repository_info();

                if (!isset($this->github_response['tag_name'])) {
                    return $transient;
                }

                $out_of_date = version_compare($this->github_response['tag_name'], $checked[$this->basename], 'gt');
                if ($out_of_date) {
                    $slug = current(explode('/', $this->basename));

                    $plugin = array(
                        'url'         => $this->github_response['html_url'],
                        'slug'        => $slug,
                        'package'     => $this->github_response['zipball_url'],
                        'new_version' => $this->github_response['tag_name']
                    );

                    $transient->response[$this->basename] = (object) $plugin;
                }
            }
        }

        return $transient;
    }

    public function plugin_popup($result, $action, $args) {
        if (!empty($args->slug) && $args->slug == current(explode('/', $this->basename))) {
            $this->get_repository_info();

            $plugin = array(
                'name'              => $this->github_response['name'],
                'slug'              => $this->basename,
                'requires'          => '5.6',
                'tested'            => '1000',
                'rating'            => '100.0',
                'num_ratings'       => '10823',
                'downloaded'        => '14249',
                'added'             => '2023-01-05',
                'version'           => $this->github_response['tag_name'],
                'author'            => $this->github_response['author']['login'],
                'author_profile'    => $this->github_response['author']['html_url'],
                'last_updated'      => $this->github_response['published_at'],
                'homepage'          => $this->github_response['html_url'],
                'short_description' => $this->github_response['body'],
                'sections'          => array(
                    'Description' => $this->github_response['body'],
                ),
                'download_link'     => $this->github_response['zipball_url']
            );

            return (object) $plugin;
        }

        return $result;
    }
}

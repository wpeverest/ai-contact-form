<?php

namespace EverestForms\AI;

class GithubUpdater {

    private $file;
    private $plugin;
    private $basename;
    private $active;
    private $username;
    private $repository;
    private $github_response;

    public function __construct($file) {
        $this->file = $file;
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

            $response = json_decode(
                wp_remote_retrieve_body(wp_remote_get($request_uri)),
                true
            );

            if (is_array($response)) {
                $response = current($response);
            }

            $this->github_response = $response;
        }
    }

    public function initialize() {
        add_filter(
            'pre_set_site_transient_update_plugins',
            array($this, 'modify_transient'),
            10,
            1
        );
        add_filter('plugins_api', array($this, 'plugin_popup'), 10, 3);
        add_filter('upgrader_post_install', array($this, 'after_install'), 10, 3);
        add_filter('upgrader_pre_download', array($this, 'download_package'));
    }

	public function modify_transient( $transient ) {

		if ( property_exists( $transient, 'checked' ) ) {

			if ( $checked = $transient->checked ) {
				$this->get_repository_info();

				if ( ! isset( $this->github_response['tag_name'] ) ) {
					return;
				}
				$out_of_date = version_compare( $this->github_response['tag_name'], $checked[ $this->basename ], 'gt' );

				if ( $out_of_date ) {

					$new_files = $this->github_response['zipball_url'];
					$slug = current( explode( '/', $this->basename ) );

					if ( ! isset( $this->plugin['PluginURI'] ) ) {
						return;
					}
					$plugin = array(
						'url'         => $this->plugin['PluginURI'],
						'slug'        => $slug,
						'package'     => $new_files,
						'new_version' => $this->github_response['tag_name']
					);

					$transient->response[ $this->basename ] = (object) $plugin;
				}
			}
		}

		return $transient;
	}

	public function plugin_popup( $result, $action, $args ) {

		if ( ! empty( $args->slug ) ) {

			if ( $args->slug == current( explode( '/', $this->basename ) ) ) {

				$this->get_repository_info();


				$plugin = array(
					'name'              => $this->plugin['Name'],
					'slug'              => $this->basename,
					'requires'          => '5.6',
					'tested'            => '1000',
					'rating'            => '100.0',
					'num_ratings'       => '10823',
					'downloaded'        => '14249',
					'added'             => '2023-01-05',
					'version'           => $this->github_response['tag_name'],
					'author'            => $this->plugin['AuthorName'],
					'author_profile'    => $this->plugin['AuthorURI'],
					'last_updated'      => $this->github_response['published_at'],
					'homepage'          => $this->plugin['PluginURI'],
					'short_description' => $this->plugin['Description'],
					'sections'          => array(
						'Description' => $this->plugin['Description'],
						'Updates'     => $this->github_response['body'],
					),
					'download_link'     => $this->github_response['zipball_url']
				);

				return (object) $plugin;
			}
		}
		return $result;
	}

	public function download_package( $args, $url ) {

		if ( null !== $args['filename'] ) {
			if ( $this->authorize_token ) {
				$args = array_merge( $args, array( 'headers' => array( 'Authorization' => "token {$this->authorize_token}" ) ) );
			}
		}

		remove_filter( 'http_request_args', array( $this, 'download_package' ) );

		return $args;
	}

	public function after_install( $response, $hook_extra, $result ) {
		global $wp_filesystem;

		$install_directory = plugin_dir_path( $this->file );
		$wp_filesystem->move( $result['destination'], $install_directory );
		$result['destination'] = $install_directory;

		if ( $this->active ) {
			activate_plugin( $this->basename );
		}

		return $result;
	}

}

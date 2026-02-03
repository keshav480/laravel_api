<?php
/*
 * This is the child theme for Hello Elementor theme, generated with Generate Child Theme plugin by catchthemes.
 *
 * (Please see https://developer.wordpress.org/themes/advanced-topics/child-themes/#how-to-create-a-child-theme)
 */
add_action( 'wp_enqueue_scripts', 'hello_elementor_child_enqueue_styles' );
function hello_elementor_child_enqueue_styles() {
    wp_enqueue_style( 'parent-style', get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('parent-style')
    );
}
/*
 * Your code goes below
 */


// Cron job 
add_action('user_register', 'schedule_user_export_to_monday');
function schedule_user_export_to_monday($user_id) {
    if (! wp_next_scheduled('user_export_to_monday_cron', [$user_id])) {
        wp_schedule_single_event(time(), 'user_export_to_monday_cron', [$user_id]);
    }
	
}
add_action('user_export_to_monday_cron', 'export_user_to_monday_cron');
function export_user_to_monday_cron($user_id) {
    $user = get_userdata($user_id);
    if ($user) {
        export_wp_users_to_monday($user);
    }
	
}
function export_wp_users_to_monday($user) {
	if (empty($user)) {
        return;
    }
    $api_token = 'eyJhbGciOiJIUzI1NiJ9.eyJ0aWQiOjYxNDk5NjEwOSwiYWFpIjoxMSwidWlkIjo5OTEzODIwNSwiaWFkIjoiMjAyNi0wMi0wMlQwNjozMzoxNS4yNzdaIiwicGVyIjoibWU6d3JpdGUiLCJhY3RpZCI6MzM1OTIyODYsInJnbiI6ImFwc2UyIn0.e1fUR1ttPPQiUeRK3IGRaOTuxCED4Ere3FIAfVjhixs';
    $board_id  = 5026382340;
		$column_values = [
			'numeric_mm06n9g9'=>$user->ID,
			'name'     => $user->display_name,
			'email_mm067jx'   => [
				'email' => $user->user_email,
				'text'  => $user->user_email,
			],
			'text_mm06gdac' => implode(', ', array_map('ucfirst', $user->roles)),
			'status' => [
				'label' => $user->user_status === '0' ? 'Active' : 'Inactive',
			],
			'date_mm06ehx6' => [
				'date' => date('Y-m-d', strtotime($user->user_registered)),
			],
		];
	
		$mutation = '
		mutation ($boardId: ID!, $itemName: String!, $columnVals: JSON!) {
			create_item(
				board_id: $boardId,
				item_name: $itemName,
				column_values: $columnVals
			) {
				id
			}
		}';

		$query = 'mutation ($boardId: ID!, $groupId: String!, $itemName: String!, $columnValues: JSON!) {
			create_item (board_id: $boardId, group_id: $groupId, item_name: $itemName, column_values: $columnValues) {
				id
			}
		}';
	
		$response = wp_remote_post(
			'https://api.monday.com/v2/',
			[
				'headers' => [
					'Authorization' => $api_token,
					'Content-Type'  => 'application/json',
				],
				'body' => wp_json_encode([
					'query'     => $mutation,
					'variables' => [
						'boardId'    => (string) $board_id,
						'itemName'   => $user->display_name,
						'columnVals' => wp_json_encode($column_values),
					],
				]),
				'timeout' => 20,
			]
		);
		if (is_wp_error($response)) {
			error_log('Monday WP Error: ' . $response->get_error_message());
		}
		$body   = wp_remote_retrieve_body($response);
		$result = json_decode($body, true);
		if (empty($result['data']['create_item']['id'])) {
			error_log('Monday API Error: ' . print_r($result, true));
		}	
		return $response ;
}




// custom poup 
add_action('init', 'handle_custom_register');

function handle_custom_register() {
    if (isset($_POST['register_submit'])) {

        // Basic sanitization
        $username = sanitize_user($_POST['username']);
        $password = sanitize_text_field($_POST['password']);
        $confirm_password = sanitize_text_field($_POST['confirm_password']);
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $state = sanitize_text_field($_POST['state']);
        $county = sanitize_text_field($_POST['county']);
        $city = sanitize_text_field($_POST['city']);

        // Validate
        if (username_exists($username)) {
            echo '<p style="color:red">Username already exists.</p>';
            return;
        }

        if ($password !== $confirm_password) {
            echo '<p style="color:red">Passwords do not match.</p>';
            return;
        }

        // Create the user
        $user_id = wp_create_user($username, $password, '');
        if (is_wp_error($user_id)) {
            echo '<p style="color:red">Error creating user.</p>';
            return;
        }

        // Update additional fields
        wp_update_user([
            'ID' => $user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
        ]);

        // Save custom meta fields
        update_user_meta($user_id, 'state', $state);
        update_user_meta($user_id, 'county', $county);
        update_user_meta($user_id, 'city', $city);

        echo '<p style="color:green">User registered successfully!</p>';
    }
}

function custom_tabbed_login_register_popup() {

    if ( is_user_logged_in() ) {
        return;
    }
    ?>
    <!-- Popup HTML -->
    <div id="custom-popup" class="popup-overlay">
        <div class="popup-content">

            <span class="popup-close">&times;</span>
			<h2 class="popup-title">Welcome Back 👋</h2>
            <!-- Tabs -->
            <div class="popup-tabs">
                <button class="tab-btn active" data-tab="login-tab">Login</button>
                <button class="tab-btn" data-tab="register-tab">Register</button>
            </div>

            <!-- Tab Contents -->
            <div class="tab-content active" id="login-tab">
                <?php
                wp_login_form( array(
                    'redirect'       => esc_url( $_SERVER['REQUEST_URI'] ),
                    'label_username' => 'Username or Email',
                    'label_password' => 'Password',
                    'label_log_in'   => 'Login',
                ) );
                ?>
            </div>

            <div class="tab-content" id="register-tab">
                <?php if ( get_option( 'users_can_register' ) ) : ?>
                    <form method="post" action="<?php echo esc_url( site_url('wp-login.php?action=register', 'login_post') ); ?>">
                        <p>
                            <input type="text" name="user_login" placeholder="Username" required>
                        </p>
                        <p>
                            <input type="email" name="user_email" placeholder="Email" required>
                        </p>
                        <p>
                            <input type="submit" value="Register">
                        </p>
                    </form>
                <?php else: ?>
					
					<form id="custom-register-form" method="post">
						<p><input type="text" name="first_name" placeholder="First Name" required></p>
						<p><input type="text" name="last_name" placeholder="Surname" required></p>
						<p><input type="text" name="username" placeholder="Username" required></p>
						<p><input type="password" name="password" placeholder="Password" required></p>
						<p><input type="password" name="confirm_password" placeholder="Confirm Password" required></p>
						<p><input type="text" name="state" placeholder="State"></p>
						<p><input type="text" name="county" placeholder="County"></p>
						<p><input type="text" name="city" placeholder="City"></p>
						<p><input type="submit" name="register_submit" value="Register"></p>
						<p class="register-message" style="color:red;"></p>
					</form>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- CSS -->
    <style>
		p.register-submit {
    width: 100%;
}
		form#custom-register-form {
			display: flex;
			flex-wrap: wrap;
			gap: 23px;
			justify-content: center;
		}
        .popup-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.65);
            backdrop-filter: blur(5px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 99999;
        }

        .popup-content {
            background: #fff;
            max-width: 497px;
            width: 90%;
            padding: 35px 30px;
            border-radius: 14px;
            position: relative;
            box-shadow: 0 25px 60px rgba(0,0,0,0.3);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }

        .popup-close {
            position: absolute;
            top: 14px;
            right: 16px;
            font-size: 26px;
            color: #999;
            cursor: pointer;
        }

        .popup-close:hover {
            color: #111;
            transform: rotate(90deg);
        }

        /* Tabs */
        .popup-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 2px solid #eee;
        }
        .popup-tabs .tab-btn {
            flex: 1;
            padding: 12px 0;
            cursor: pointer;
            border: none;
            background: none;
            font-weight: 600;
            color: #555;
            transition: 0.3s;
            outline: none;
        }
        .popup-tabs .tab-btn.active {
            color: #2271b1;
            border-bottom: 3px solid #2271b1;
        }

        /* Tab Content */
        .tab-content {
            display: none;
            animation: fadeIn 0.3s ease;
        }
        .tab-content.active {
            display: block;
        }

        /* Form Inputs */
        .tab-content input[type="text"],
        .tab-content input[type="password"],
        .tab-content input[type="email"] {
            width: 100%;
            padding: 14px;
            margin-bottom: 14px;
            border-radius: 10px;
            border: 1px solid #ddd;
            font-size: 15px;
        }

        .tab-content input[type="submit"] {
            width: 100%;
            padding: 14px;
            border-radius: 10px;
            border: none;
            font-size: 16px;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, #2271b1, #135e96);
            cursor: pointer;
            transition: 0.3s;
        }
        .tab-content input[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(34,113,177,0.35);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>

    <!-- jQuery -->
    <script>
        jQuery(document).ready(function ($) {

            var popup = $('#custom-popup');

            // Show popup after 3 seconds
            setTimeout(function () {
                popup.fadeIn(300).css('display','flex');
            }, 3000);

            // Close popup
            $('.popup-close').on('click', function () {
                popup.fadeOut(300);
            });

            // Close on overlay click
            popup.on('click', function (e) {
                if ($(e.target).is(popup)) {
                    popup.fadeOut(300);
                }
            });

            // Tab switch
            $('.tab-btn').on('click', function () {
                var tab = $(this).data('tab');

                $('.tab-btn').removeClass('active');
                $(this).addClass('active');

                $('.tab-content').removeClass('active');
                $('#' + tab).addClass('active');
            });

        });
    </script>
    <?php
}
add_action('wp_footer', 'custom_tabbed_login_register_popup');


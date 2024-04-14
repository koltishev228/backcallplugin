<?php
/*
 * Plugin Name: Back Call Plugin
 * Description: Вывод списка заголовков h2 и h3 с якорями в начале описания товаров и категорий.
 * Text Domain: back-call-plugin
 */

defined('ABSPATH') or die('Something wrong');

class BackCallPlugin
{
	protected $options = 'back_call_plugin_settings';

	public function __construct()
	{
		// Регистрация шорткода
		add_shortcode('contact_manager_button', array($this, 'contact_manager_button_shortcode'));

		// Загрузка скриптов модального окна
		add_action('wp_enqueue_scripts', array($this, 'load_modal_scripts'));

		// Обработка отправки формы обратной связи
		add_action('init', array($this, 'process_back_call_form'));

		// Добавление страницы настроек плагина
		add_action('admin_menu', array($this, 'add_plugin_page'));

		// Регистрация настроек плагина
		add_action('admin_init', array($this, 'register_settings'));
	}

	public function add_plugin_page()
	{
		add_options_page(
			'Back Call Plugin Settings',
			'Back Call Plugin',
			'manage_options',
			'back-call-plugin-settings',
			array($this, 'create_admin_page')
		);
	}

	public function create_admin_page()
	{
		// Получаем сохраненные настройки, если они есть
		$options = get_option($this->options);
		$enabled = $options['back_call_enabled'] ?? false;
        $back_call_title = $options['back_call_title'] ?? 'Call';
        $back_call_email_admin = $options['back_call_email_admin'] ?? 'test@gmail.com';
        $back_call_underscore = $options['back_call_underscore'] ?? false;

        $back_call_name_text = $options['back_call_name_text'] ?? 'Name';
        $back_call_phone_text = $options['back_call_phone_text'] ?? 'Phone';
        $back_call_email_text = $options['back_call_email_text'] ?? 'Email';
        $back_call_button_text = $options['back_call_button_text'] ?? 'Send';
        $back_call_thank_text = $options['back_call_thank_text'] ?? 'Thank you.manager will cal you';

		?>
		<div class="wrap">
			<h2>Back Call Plugin Settings</h2>
			<form method="post" action="options.php">
				<?php
				// Выводим скрытые поля для настроек
				settings_fields($this->options);
				do_settings_sections('back-call-plugin-settings');
				?>

                    <p>
                        <label for="back-call-email">
                            Back call email

                            <input type="email" id="back-call-email" name="<?php echo $this->options; ?>[back_call_email_admin]" value="<?php echo $back_call_email_admin?>"
                        </label>
                    </p>
                <p>
				<label for="back-call-enabled">
                    Enable back call

                    <input type="checkbox" id="back-call-enabled" name="<?php echo $this->options; ?>[back_call_enabled]" <?php checked($enabled, true); ?>>
				</label>
                </p>

                <p>
                    <label for="back-call-enabled">
                        Button with underscore?
                        <input type="checkbox" id="back-call-underscore" name="<?php echo $this->options; ?>[back_call_underscore]" <?php checked($back_call_underscore, true); ?>>
                    </label>
                </p>
                <p>
                <label for="back-call-title">
                    Back call title

                    <input type="text" id="back-call-title" name="<?php echo $this->options; ?>[back_call_title]"  value="<?php echo $back_call_title;?>">
                </label>
                </p>

                <div class="wrap">
                    <h3>Back Call Plugin texts</h3>
                    <p>
                        <label for="">
                            Text for name
                            <input type="text" id="back-call-name-text" name="<?php echo $this->options; ?>[back_call_name_text]"  value="<?php echo $back_call_name_text;?>">
                        </label>
                    </p>

                    <p>
                        <label for="">
                            Text for phone
                            <input type="text" id="back-call-phone-text" name="<?php echo $this->options; ?>[back_call_phone_text]"  value="<?php echo $back_call_phone_text;?>">
                        </label>
                    </p>


                    <p>
                        <label for="">
                            Text for email
                            <input type="text" id="back-call-email-text" name="<?php echo $this->options; ?>[back_call_email_text]"  value="<?php echo $back_call_email_text;?>">
                        </label>
                    </p>


                    <p>
                        <label for="">
                            Text for button
                            <input type="text" id="back-call-button-text" name="<?php echo $this->options; ?>[back_call_button_text]"  value="<?php echo $back_call_button_text;?>">
                        </label>
                    </p>


                    <p>
                        <label for="">
                            Text for thank modal
                            <input type="text" id="back-call-thank-text" name="<?php echo $this->options; ?>[back_call_thank_text]"  value="<?php echo $back_call_thank_text;?>">
                        </label>
                    </p>

                </div>
				<div class="wrap">
					<h3>Back Call Plugin instruction</h3>
					<p>use shortcode for button <code>[contact_manager_button]</code></p>
				</div>
				<?php
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function register_settings()
	{
		register_setting($this->options, $this->options, array($this, 'sanitize_settings'));
	}

	public function load_modal_scripts()
	{
		wp_enqueue_style('modal-style', plugin_dir_url(__FILE__) . 'modal.css');
		wp_enqueue_script('modal-script', plugin_dir_url(__FILE__) . 'modal.js', array('jquery'), null, true);
	}

    function process_back_call_form() {
        $options = get_option($this->options);

        if (isset($_POST['back_call_name']) && isset($_POST['back_call_email']) && isset($_POST['back_call_phone'])) {
            $name  = sanitize_text_field($_POST['back_call_name']);
            $email = sanitize_email($_POST['back_call_email']);
            $phone = sanitize_text_field($_POST['back_call_phone']);

            // Получение email адреса администратора
            $admin_email = $options['back_call_email_admin'];

            // Создание темы и тела сообщения
            $subject = 'New order from ' . $name;
            $message = "New order details:\r\n";
            $message .= "Name: " . $name . "\r\n";
            $message .= "Email: " . $email . "\r\n";
            $message .= "Phone: " . $phone . "\r\n";

            // Добавление URL страницы, если доступно
            if (isset($_SERVER['HTTP_REFERER'])) {
                $referer = sanitize_text_field($_SERVER['HTTP_REFERER']);
                $message .= "Page URL: " . $referer . "\r\n";
            } else {
                $message .= "Page URL: Referer not available.\r\n";
            }
            print_r($message);

            wp_mail($admin_email, $subject, $message);
        }
    }



    public function contact_manager_button_shortcode() {
        $options = get_option('back_call_plugin_settings');
        $enabled = $options['back_call_enabled'];
        $back_call_title = $options['back_call_title'];
        $back_call_underscore = $options['back_call_underscore'] ?? false;

        $back_call_name_text = $options['back_call_name_text'];
        $back_call_phone_text = $options['back_call_phone_text'];
        $back_call_email_text = $options['back_call_email_text'];
        $back_call_button_text = $options['back_call_button_text'];
        $back_call_thank_text = $options['back_call_thank_text'];

        if ($enabled) {
            ?>
            <button style="text-decoration: <?php echo $back_call_underscore ? 'underline' : 'none'?>" id="open-modal"><?php echo $back_call_title; ?></button>
            <div id="modal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <form id="contact-form" action="#" method="post">
                        <p><label for="back-call-name"><?php echo $back_call_name_text; ?></label><br><input type="text" name="back_call_name" id="back-call-name"></p>
                        <p><label for="back-call-email"><?php echo $back_call_email_text; ?></label><br><input required type="email" name="back_call_email" id="back-call-email"></p>
                        <p><label for="back-call-phone"><?php echo $back_call_phone_text; ?></label><br><input type="text" name="back_call_phone" id="back-call-phone" placeholder="Format +XX-XXX-XXX-XXXX"></p>
                        <p><input type="submit" value="<?php echo $back_call_button_text; ?>"></p>
                    </form>
                </div>
            </div>
            <div id="thank-you-modal" class="modal" style="display: none;">
                <div class="modal-content">
                    <span class="close-thank-you">&times;</span>
                    <p><?php echo $back_call_thank_text; ?></p>
                </div>
            </div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var form = document.getElementById('contact-form');
                    document.getElementById('open-modal').addEventListener('click', function() {
                        document.getElementById('modal').style.display = 'block';
                    });
                    document.querySelector('.close').addEventListener('click', function() {
                        document.getElementById('modal').style.display = 'none';
                    });

                    form.addEventListener('submit', function(event) {
                        event.preventDefault(); // Предотвращение стандартной отправки формы
                        var formData = new FormData(form);
                        fetch(form.action, {
                            method: 'POST',
                            body: formData
                        }).then(response => response.text())
                            .then(html => {
                                document.getElementById('modal').style.display = 'none';
                                var thankYouModal = document.getElementById('thank-you-modal');
                                thankYouModal.style.display = 'block';
                                setTimeout(function() {
                                    thankYouModal.style.display = 'none';
                                }, 3000);
                            });
                    });
                });
            </script>
            <?php
        }
    }


	public function sanitize_settings($input)
	{
		$output = array();
		if (isset($input['back_call_enabled'])) {
			$output['back_call_enabled'] = (bool) $input['back_call_enabled'];
		}
        if (isset($input['back_call_underscore'])) {
            $output['back_call_underscore'] = (bool) $input['back_call_underscore'];
        }
        if (isset($input['back_call_title'])) {
            $output['back_call_title'] = (string) $input['back_call_title'];
        }
        if (isset($input['back_call_email_admin'])) {
            $output['back_call_email_admin'] = (string) $input['back_call_email_admin'];
        }
        if (isset($input['back_call_name_text'])) {
            $output['back_call_name_text'] = (string) $input['back_call_name_text'];
        }
        if (isset($input['back_call_phone_text'])) {
            $output['back_call_phone_text'] = (string) $input['back_call_phone_text'];
        }
        if (isset($input['back_call_email_text'])) {
            $output['back_call_email_text'] = (string) $input['back_call_email_text'];
        }
        if (isset($input['back_call_button_text'])) {
            $output['back_call_button_text'] = (string) $input['back_call_button_text'];
        }
        if (isset($input['back_call_thank_text'])) {
            $output['back_call_thank_text'] = (string) $input['back_call_thank_text'];
        }
		return $output;
	}
}

if (class_exists('BackCallPlugin')) {
	$backCallPlugin = new BackCallPlugin();
}

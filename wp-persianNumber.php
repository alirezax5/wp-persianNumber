<?php
/*
Plugin Name: Persian Number Converter
Plugin URI: https://github.com/alirezax5/wp-persianNumber
Description: تبدیل اعداد انگلیسی به فارسی در کل وردپرس + صفحه تنظیمات + پشتیبانی کامل ووکامرس
Version: 1.0
Author: alirezax5
License: GPL2
*/

if (!defined('ABSPATH')) exit;

class wpPersianNumber
{

    private $options;
    private const FA_DIGITS = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
    private const EN_DIGITS = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    public function __construct()
    {

        // تنظیمات پیش‌فرض
        $this->options = get_option('pnc_settings', [
                'content' => 1,
                'title' => 1,
                'excerpt' => 1,
                'widget' => 1,
                'comment' => 1,
                'meta' => 1,
                'wc_price' => 1,
                'wc_cart' => 1,
        ]);

        // وردپرس
        $this->addWordPressFilters();
        // ووکامرس
        $this->addWooCommerceFilters();


        // صفحه تنظیمات
        add_action('admin_menu', [$this, 'menu']);
        add_action('admin_init', [$this, 'save']);
    }

    private function addWordPressFilters(): void
    {
        if (($this->options['content'] ?? 0)) {
            add_filter('the_content', [$this, 'convert']);
        }
        if (($this->options['title'] ?? 0)) {
            add_filter('the_title', [$this, 'convert']);
        }
        if (($this->options['excerpt'] ?? 0)) {
            add_filter('the_excerpt', [$this, 'convert']);
        }
        if (($this->options['widget'] ?? 0)) {
            add_filter('widget_text', [$this, 'convert']);
        }
        if (($this->options['comment'] ?? 0)) {
            add_filter('comment_text', [$this, 'convert']);
        }

        if (($this->options['meta'] ?? 0)) {
            add_filter('get_post_metadata', [$this, 'convert_meta'], 10, 4);
        }
    }

    private function addWooCommerceFilters(): void
    {
        if (($this->options['wc_price'] ?? 0)) {
            $filters = [
                    'woocommerce_get_price_html',
                    'woocommerce_cart_item_price',
                    'woocommerce_cart_item_subtotal',
                    'woocommerce_cart_subtotal',
                    'woocommerce_cart_totals_order_total_html',
                    'woocommerce_checkout_cart_item_quantity',
                    'woocommerce_order_formatted_line_subtotal',
            ];
            foreach ($filters as $filter) {
                add_filter($filter, [$this, 'convert'], 99);
            }
        }

        if (($this->options['wc_cart'] ?? 0)) {
            add_filter('woocommerce_cart_tax_totals', [$this, 'convert_array']);
            add_filter('woocommerce_shipping_rate_label', [$this, 'convert'], 99);
        }
    }

    public function convert(?string $text): ?string
    {
        if (!is_string($text)) {
            return $text;
        }

        return strtr($text, [
                ...array_combine(self::EN_DIGITS, self::FA_DIGITS),
        ]);
    }
    public function convert_array($array)
    {
        foreach ($array as $key => $value) {
            if (is_string($value)) {
                $array[$key] = $this->convert($value);
            }
        }
        return $array;
    }

    public function convert_meta($value)
    {
        if (is_array($value)) {
            return array_map(function ($v) {
                return is_string($v) ? $this->convert($v) : $v;
            }, $value);
        }
        return is_string($value) ? $this->convert($value) : $value;
    }

    /*
    ===============================================
    ==========   تنظیمات و منو   ==================
    ===============================================
    */

    public function menu()
    {
        add_options_page(
                'Persian Number Converter',
                'Persian Numbers',
                'manage_options',
                'pnc-settings',
                [$this, 'settings_page']
        );
    }

    public function settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>تنظیمات Persian Number Converter</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('pnc_group');
                do_settings_sections('pnc-settings');
                submit_button();
                ?>
            </form>

            <h2>توضیحات</h2>
            <p>این پلاگین تنها <strong>خروجی</strong> را تغییر می‌دهد و دیتابیس را ویرایش نمی‌کند. )</p></div>
        <?php
    }

    public function save()
    {

        register_setting('pnc_group', 'pnc_settings');

        add_settings_section('pnc_main', 'گزینه ها', null, 'pnc-settings');

        $fields = [
                'content' => 'تبدیل در محتوا',
                'title' => 'تبدیل در عنوان‌ها',
                'excerpt' => 'تبدیل در خلاصه',
                'widget' => 'تبدیل در ویجت‌ها',
                'comment' => 'تبدیل در کامنت‌ها',
                'meta' => 'تبدیل فیلدهای سفارشی (Meta)',
                'wc_price' => 'تبدیل قیمت‌های ووکامرس',
                'wc_cart' => 'تبدیل بخش سبد خرید و مالیات ووکامرس',
        ];

        foreach ($fields as $key => $label) {
            add_settings_field(
                    $key,
                    $label,
                    function () use ($key) {
                        $checked = !empty($this->options[$key]) ? 'checked' : '';
                        echo "<input type='checkbox' name='pnc_settings[$key]' value='1' $checked>";
                    },
                    'pnc-settings',
                    'pnc_main'
            );
        }
    }
}

new wpPersianNumber();

<?php
/*
Plugin Name: 小半自定义网址导航
Description: 自定义导航插件，支持分类导航、前台页面展示、公告、广告设置及LyToday-JS热搜榜插件。
Plugin URI: https://www.jingxialai.com/4980.html
Version: 1.0.1
Author: Summer
License: GPL License
Author URI: https://www.jingxialai.com/
*/

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 定义插件路径常量
define('XB_NAV_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('XB_NAV_PLUGIN_URL', plugin_dir_url(__FILE__));

// 插件激活时执行
register_activation_hook(__FILE__, 'xb_nav_activate');
function xb_nav_activate() {
    xb_nav_create_table(); // 创建或更新数据表
    xb_nav_create_page();  // 创建前台页面
}

// 创建或更新数据表
function xb_nav_create_table() {
    global $wpdb;
    $table_nav = $wpdb->prefix . 'xb_nav';
    $table_category = $wpdb->prefix . 'xb_nav_categories';
    $charset_collate = $wpdb->get_charset_collate();

    // 导航分类表
    $sql_category = "CREATE TABLE IF NOT EXISTS $table_category (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        icon VARCHAR(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    
    // 导航内容表
    $sql_nav = "CREATE TABLE IF NOT EXISTS $table_nav (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name VARCHAR(255) NOT NULL,
        url VARCHAR(255) NOT NULL,
        icon VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        intro_url VARCHAR(255) NOT NULL,
        category_id BIGINT(20) UNSIGNED NOT NULL,
        redirect_to_intro TINYINT(1) DEFAULT 0,
        PRIMARY KEY (id),
        FOREIGN KEY (category_id) REFERENCES $table_category(id) ON DELETE CASCADE
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_category);
    dbDelta($sql_nav);
}

// 创建前台页面，检查是否已有包含短代码的页面
function xb_nav_create_page() {
    $pages = get_posts(array(
        'post_type'   => 'page',
        'post_status' => 'publish',
        's'           => '[xb_nav_page]',
        'numberposts' => 1,
    ));

    if (empty($pages)) {
        wp_insert_post(array(
            'post_title'   => '自定义导航页面',
            'post_content' => '[xb_nav_page]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ));
    }
}

// 加载前台 CSS 和 JS 文件，仅在包含 [xb_nav_page] 短代码的页面加载
add_action('wp_enqueue_scripts', 'xb_nav_enqueue_scripts');
function xb_nav_enqueue_scripts() {
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'xb_nav_page')) {
        wp_enqueue_style('xb-nav-style', XB_NAV_PLUGIN_URL . 'assets/xb-nav.css', array(), '2.5');
        wp_enqueue_script('xb-nav-script', XB_NAV_PLUGIN_URL . 'assets/xb-nav.js', array('jquery'), '2.5', true);
        wp_localize_script('xb-nav-script', 'xbNavAjax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'show_desc' => get_option('xb_nav_show_desc', '0'),
        ));

        // 动态添加背景颜色样式
        $background_color = get_option('xb_nav_background_color', 'linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%)');
        $custom_css = ".xb-nav-container { background: {$background_color}; }";
        wp_add_inline_style('xb-nav-style', $custom_css);

        // 加载 Iconfont CSS 如果设置了
        $iconfont_url = get_option('xb_nav_iconfont_url', '');
        if (!empty($iconfont_url)) {
            wp_enqueue_style('xb-nav-iconfont', esc_url($iconfont_url), array(), null);
        }
    }
}

// 短代码注册，用于显示前台页面
add_shortcode('xb_nav_page', 'xb_nav_display_page');
function xb_nav_display_page() {
    global $wpdb;
    $table_nav = $wpdb->prefix . 'xb_nav';
    $table_category = $wpdb->prefix . 'xb_nav_categories';

    // 获取所有分类
    $categories = $wpdb->get_results("SELECT * FROM $table_category ORDER BY id ASC");

    // 获取所有导航
    $navs = $wpdb->get_results("SELECT n.*, c.name as category_name FROM $table_nav n LEFT JOIN $table_category c ON n.category_id = c.id ORDER BY n.category_id ASC");

    // 获取设置
    $announcement = get_option('xb_nav_announcement', '');
    $ad_content = get_option('xb_nav_ad_content', '');
    $show_nav_count = get_option('xb_nav_show_count', '0');
    $apply_url = get_option('xb_nav_apply_url', '');
    $show_apply = get_option('xb_nav_show_apply', '0');
    $show_posts = get_option('xb_nav_show_posts', '0');
    $post_type = get_option('xb_nav_post_type', 'recent');
    $post_count = get_option('xb_nav_post_count', 3);
    $page_title = get_option('xb_nav_page_title', '自定义导航页面');
    $show_page_title = get_option('xb_nav_show_page_title', '1');
    $enable_clock = get_option('xb_nav_enable_clock', '0');
    $enable_lytoday = get_option('xb_nav_enable_lytoday', '0');
    
    // 获取联系方式设置
    $contact_qq = get_option('xb_nav_contact_qq', '');
    $contact_city = get_option('xb_nav_contact_city', '');
    $contact_email = get_option('xb_nav_contact_email', '');
    $contact_bilibili_url = get_option('xb_nav_contact_bilibili_url', '');
    $contact_bilibili_text = get_option('xb_nav_contact_bilibili_text', '');
    $contact_weibo_url = get_option('xb_nav_contact_weibo_url', '');
    $contact_netease_url = get_option('xb_nav_contact_netease_url', '');
    $contact_xiaohongshu_qr = get_option('xb_nav_contact_xiaohongshu_qr', '');
    $contact_douyin_qr = get_option('xb_nav_contact_douyin_qr', '');
    $contact_wechat_qr = get_option('xb_nav_contact_wechat_qr', '');
    $iconfont_url = get_option('xb_nav_iconfont_url', '');

    ob_start();
    ?>
    <div class="xb-nav-container">
        <?php if ($show_page_title === '1'): ?>
            <div class="xb-title"><?php echo esc_html($page_title); ?></div>
        <?php endif; ?>

        <!-- 搜索板块 -->
        <div class="xb-nav-search">
            <div class="xb-search-tabs">
                <button class="xb-search-tab active" data-engine="baidu">百度</button>
                <button class="xb-search-tab" data-engine="google">谷歌</button>
                <button class="xb-search-tab" data-engine="github">GitHub</button>
                <button class="xb-search-tab" data-engine="bilibili">B站</button>
                <button class="xb-search-tab" data-engine="weibo">微博</button>
                <button class="xb-search-tab" data-engine="zhihu">知乎</button>
                <button class="xb-search-tab" data-engine="taobao">淘宝</button>
                <button class="xb-search-tab" data-engine="site">站内搜索</button>
            </div>
            <div class="xb-search-input-wrapper">
                <input type="text" id="xb-search-input" placeholder="输入关键词搜索..." />
                <button id="xb-search-btn">
                    <svg class="xb-search-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                    </svg>
                </button>
            </div>
        </div>

        <!-- 手机端菜单栏 -->
        <div class="xb-mobile-menu">
            <span class="menu-icon">☰</span>
            <div class="xb-mobile-menu-content">
                <div class="xb-mobile-menu-header">
                    <span class="xb-title">导航分类</span>
                    <span class="menu-close">×</span>
                </div>
                <ul>
                    <?php foreach ($categories as $category): ?>
                        <li class="xb-category-item" data-category-id="<?php echo esc_attr($category->id); ?>">
                            <img src="<?php echo esc_url($category->icon); ?>" alt="<?php echo esc_attr($category->name); ?>" class="xb-category-icon" />
                            <span><?php echo esc_html($category->name); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- 主内容 -->
        <div class="xb-nav-main">
            <!-- 左边分类栏（桌面端显示） -->
            <div class="xb-nav-sidebar">
                <div class="xb-title">导航分类</div>
                <ul>
                    <?php foreach ($categories as $category): ?>
                        <li class="xb-category-item" data-category-id="<?php echo esc_attr($category->id); ?>">
                            <img src="<?php echo esc_url($category->icon); ?>" alt="<?php echo esc_attr($category->name); ?>" class="xb-category-icon" />
                            <span><?php echo esc_html($category->name); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- 中间导航内容 -->
            <div class="xb-nav-content">
                <?php
                $current_category = '';
                foreach ($navs as $nav):
                    if ($nav->category_name !== $current_category):
                        if ($current_category !== '') echo '</div></div>';
                        $current_category = $nav->category_name;
                        ?>
                        <div class="xb-nav-section" id="category-<?php echo esc_attr($nav->category_id); ?>">
                            <div class="xb-title"><?php echo esc_html($nav->category_name); ?></div>
                            <div class="xb-nav-items">
                    <?php endif; ?>
                    <a href="<?php echo esc_url($nav->redirect_to_intro ? $nav->intro_url : $nav->url); ?>" target="_blank" class="xb-nav-item">
                        <img src="<?php echo esc_url($nav->icon); ?>" alt="<?php echo esc_attr($nav->name); ?>" class="xb-nav-icon" />
                        <div class="xb-nav-info">
                            <div class="xb-nav-name"><?php echo esc_html($nav->name); ?></div>
                            <div class="xb-nav-desc-short"><?php echo esc_html(wp_trim_words($nav->description, 10, '...')); ?></div>
                            <div class="xb-nav-desc-full"><?php echo esc_html($nav->description); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
                <?php if ($current_category !== '') echo '</div></div>'; ?>
            </div>

            <!-- 右边小工具栏 -->
            <div class="xb-nav-widgets">
                <!-- 数字时钟 -->
                <?php if ($enable_clock === '1'): ?>
                    <div class="xb-widget">
                        <div class="digital-clock">
                            <div class="time">
                                <span class="hours">00</span>
                                <span class="dots">:</span>
                                <span class="minutes">00</span>
                                <div class="right-side">
                                    <span class="seconds">00</span>
                                </div>
                            </div>
                            <div class="calender">
                                <span class="year"></span>年
                                <span class="month-name"></span>
                                <span class="day-num"></span>日
                                <span class="day-name"></span>
                            </div>
                        </div>
                    </div>
                    <script>
                        (function() {
                            function updateTime() {
                                let today = new Date();
                                let hours = today.getHours();
                                let minutes = today.getMinutes();
                                let seconds = today.getSeconds();
                                hours = hours < 10 ? "0" + hours : hours;
                                minutes = minutes < 10 ? "0" + minutes : minutes;
                                seconds = seconds < 10 ? "0" + seconds : seconds;
                                document.querySelector(".hours").innerHTML = hours;
                                document.querySelector(".minutes").innerHTML = minutes;
                                document.querySelector(".seconds").innerHTML = seconds;
                                const dayNum = today.getDate();
                                const year = today.getFullYear();
                                const dayName = today.toLocaleString("default", { weekday: "long" });
                                const monthName = today.toLocaleString("default", { month: "short" });
                                document.querySelector(".year").innerHTML = year;
                                document.querySelector(".month-name").innerHTML = monthName;
                                document.querySelector(".day-name").innerHTML = dayName;
                                document.querySelector(".day-num").innerHTML = dayNum;
                            }
                            updateTime();
                            setInterval(updateTime, 1000);
                        })();
                    </script>
                <?php endif; ?>

                <!-- 联系方式 -->
                <?php
                $has_contact = !empty($contact_qq) || !empty($contact_city) || !empty($contact_email) || 
                               !empty($contact_bilibili_url) || !empty($contact_weibo_url) || 
                               !empty($contact_netease_url) || !empty($contact_xiaohongshu_qr) || 
                               !empty($contact_douyin_qr) || !empty($contact_wechat_qr);
                if ($has_contact):
                ?>
                    <div class="xb-widget xb-contact-widget">
                        <div class="xb-title">联系方式</div>
                        <div class="xb-contact-items">
                            <?php if (!empty($contact_qq)): ?>
                                <div class="xb-contact-item">
                                    <i class="iconfont icon-qq"></i>
                                    <span><?php echo esc_html($contact_qq); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($contact_city)): ?>
                                <div class="xb-contact-item">
                                    <i class="iconfont icon-weizhi"></i>
                                    <span><?php echo esc_html($contact_city); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($contact_email)): ?>
                                <div class="xb-contact-item">
                                    <i class="iconfont icon-email"></i>
                                    <span><?php echo esc_html($contact_email); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($contact_bilibili_url)): ?>
                                <div class="xb-contact-item">
                                    <a href="<?php echo esc_url($contact_bilibili_url); ?>" target="_blank">
                                        <i class="iconfont icon-bilibili"></i>
                                        <span><?php echo esc_html($contact_bilibili_text ? $contact_bilibili_text : 'B站'); ?></span>
                                    </a>
                                </div>
                            <?php endif; ?>
                            <div class="xb-contact-row">
                                <?php if (!empty($contact_weibo_url)): ?>
                                    <div class="xb-contact-item">
                                        <a href="<?php echo esc_url($contact_weibo_url); ?>" target="_blank">
                                            <i class="iconfont icon-weibo"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($contact_xiaohongshu_qr)): ?>
                                    <div class="xb-contact-item xb-contact-qr" data-qr="<?php echo esc_url($contact_xiaohongshu_qr); ?>">
                                        <i class="iconfont icon-xiaohongshu"></i>
                                        <div class="xb-qr-popup">
                                            <img src="<?php echo esc_url($contact_xiaohongshu_qr); ?>" alt="小红书二维码" />
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($contact_douyin_qr)): ?>
                                    <div class="xb-contact-item xb-contact-qr" data-qr="<?php echo esc_url($contact_douyin_qr); ?>">
                                        <i class="iconfont icon-douyin"></i>
                                        <div class="xb-qr-popup">
                                            <img src="<?php echo esc_url($contact_douyin_qr); ?>" alt="抖音二维码" />
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($contact_wechat_qr)): ?>
                                    <div class="xb-contact-item xb-contact-qr" data-qr="<?php echo esc_url($contact_wechat_qr); ?>">
                                        <i class="iconfont icon-weixin"></i>
                                        <div class="xb-qr-popup">
                                            <img src="<?php echo esc_url($contact_wechat_qr); ?>" alt="微信二维码" />
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($contact_netease_url)): ?>
                                    <div class="xb-contact-item">
                                        <a href="<?php echo esc_url($contact_netease_url); ?>" target="_blank">
                                            <i class="iconfont icon-wangyiyun"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- 公告 -->
                <?php if (!empty($announcement)): ?>
                    <div class="xb-widget">
                        <div class="xb-title">公告</div>
                        <div class="xb-announcement"><?php echo wp_kses_post($announcement); ?></div>
                    </div>
                <?php endif; ?>

                <!-- 广告 -->
                <?php if (!empty($ad_content)): ?>
                    <div class="xb-widget">
                        <div class="xb-title">广告</div>
                        <div class="xb-ad"><?php echo wp_kses_post($ad_content); ?></div>
                    </div>
                <?php endif; ?>

                <!-- 导航数量 -->
                <?php if ($show_nav_count): ?>
                    <div class="xb-widget">
                        <div class="xb-title">收录数量</div>
                        <div class="xb-nav-count">当前收录导航：<?php echo count($navs); ?> 个</div>
                    </div>
                <?php endif; ?>

                <!-- 申请收录 -->
                <?php if ($show_apply && !empty($apply_url)): ?>
                    <div class="xb-widget">
                        <div class="xb-title">申请收录</div>
                        <a href="<?php echo esc_url($apply_url); ?>" target="_blank" class="xb-apply-btn">提交网址</a>
                    </div>
                <?php endif; ?>

                <!-- 文章显示 -->
                <?php if ($show_posts): ?>
                    <div class ="xb-widget">
                        <div class="xb-title"><?php echo $post_type === 'recent' ? '最新文章' : '随机推荐'; ?></div>
                        <?php
                        $args = array(
                            'posts_per_page' => $post_count,
                            'orderby' => $post_type === 'recent' ? 'date' : 'rand',
                        );
                        $posts = new WP_Query($args);
                        if ($posts->have_posts()):
                            $post_index = 1;
                            // 检测是否为移动设备
                            $is_mobile = wp_is_mobile();
                            $title_length = $is_mobile ? 30 : 15;
                            ?>
                            <div class="xb-post-list">
                                <?php while ($posts->have_posts()): $posts->the_post(); ?>
                                    <a href="<?php the_permalink(); ?>" target="_blank" class="xb-post-item" data-index="<?php echo $post_index; ?>">
                                        <span class="xb-post-title"><?php echo esc_html(wp_trim_words(get_the_title(), $title_length, '...')); ?></span>
                                    </a>
                                    <?php $post_index++; ?>
                                <?php endwhile; ?>
                            </div>
                            <?php
                            wp_reset_postdata();
                        endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- LyToday-JS 热搜榜 -->
        <?php if ($enable_lytoday === '1'): ?>
            <div class="xb-lytoday-container" style="max-width: 1400px; margin: 20px auto; padding: 0 20px;">
                <style>
                    .hot-panel {
                        display: flex;
                        overflow-x: auto !important;
                        flex-wrap: nowrap !important;
                        justify-content: flex-start !important;
                        align-content: center;
                    }
                    @media (max-width: 768px) {
                        .hot-panel {
                            flex-wrap: wrap !important;
                        }
                    }
                </style>
                <div id="lytoday"></div>
                <script src="https://lytoday.lylme.com/"></script>
            </div>
        <?php endif; ?>
    </div>

    <!-- 自定义提示框 -->
    <div class="xb-toast" id="xb-toast">
        <span id="xb-toast-message"></span>
    </div>

    <!-- 手机端导航开关功能 -->
    <script>
        jQuery(document).ready(function($) {
            $('.xb-mobile-menu .menu-icon').on('click', function() {
                $('.xb-mobile-menu-content').toggleClass('active');
            });
            $('.xb-mobile-menu .menu-close').on('click', function() {
                $('.xb-mobile-menu-content').removeClass('active');
            });
        });
    </script>
    <?php
    return ob_get_clean();
}

// 添加后台菜单
add_action('admin_menu', 'xb_nav_admin_menu');
function xb_nav_admin_menu() {
    add_menu_page('小半导航设置', '小半网址导航', 'manage_options', 'xb-nav-settings', 'xb_nav_settings_page', 'dashicons-admin-links');
    add_submenu_page('xb-nav-settings', '导航设置', '导航设置', 'manage_options', 'xb-nav-settings', 'xb_nav_settings_page');
    add_submenu_page('xb-nav-settings', '添加导航', '添加导航', 'manage_options', 'xb-nav-add', 'xb_nav_add_page');
    add_submenu_page('xb-nav-settings', '导航管理', '导航管理', 'manage_options', 'xb-nav-manage', 'xb_nav_manage_page');
}

// 自定义 URL 验证函数，允许协议相对 URL
function xb_sanitize_iconfont_url($url) {
    $url = trim($url);
    if (empty($url)) {
        return '';
    }
    if (preg_match('#^(//|https?://)[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(/.*)?$#', $url)) {
        return esc_url_raw($url);
    }
    return '';
}

// 验证 URL 格式
function xb_validate_url($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

// 插件设置页面
function xb_nav_settings_page() {
    if (isset($_POST['xb_nav_settings_submit'])) {
        update_option('xb_nav_announcement', wp_kses_post($_POST['xb_nav_announcement']));
        update_option('xb_nav_ad_content', wp_kses_post($_POST['xb_nav_ad_content']));
        update_option('xb_nav_show_count', isset($_POST['xb_nav_show_count']) ? '1' : '0');
        update_option('xb_nav_apply_url', esc_url_raw($_POST['xb_nav_apply_url']));
        update_option('xb_nav_show_apply', isset($_POST['xb_nav_show_apply']) ? '1' : '0');
        update_option('xb_nav_show_posts', isset($_POST['xb_nav_show_posts']) ? '1' : '0');
        update_option('xb_nav_post_type', sanitize_text_field($_POST['xb_nav_post_type']));
        update_option('xb_nav_post_count', intval($_POST['xb_nav_post_count']));
        update_option('xb_nav_page_title', sanitize_text_field($_POST['xb_nav_page_title']));
        update_option('xb_nav_show_page_title', isset($_POST['xb_nav_show_page_title']) ? '1' : '0');
        update_option('xb_nav_background_color', sanitize_text_field($_POST['xb_nav_background_color']));
        update_option('xb_nav_show_desc', isset($_POST['xb_nav_show_desc']) ? '1' : '0');
        update_option('xb_nav_enable_clock', isset($_POST['xb_nav_enable_clock']) ? '1' : '0');
        update_option('xb_nav_enable_lytoday', isset($_POST['xb_nav_enable_lytoday']) ? '1' : '0');
        update_option('xb_nav_contact_qq', sanitize_text_field($_POST['xb_nav_contact_qq']));
        update_option('xb_nav_contact_city', sanitize_text_field($_POST['xb_nav_contact_city']));
        update_option('xb_nav_contact_email', sanitize_email($_POST['xb_nav_contact_email']));
        update_option('xb_nav_contact_bilibili_url', esc_url_raw($_POST['xb_nav_contact_bilibili_url']));
        update_option('xb_nav_contact_bilibili_text', sanitize_text_field($_POST['xb_nav_contact_bilibili_text']));
        update_option('xb_nav_contact_weibo_url', esc_url_raw($_POST['xb_nav_contact_weibo_url']));
        update_option('xb_nav_contact_netease_url', esc_url_raw($_POST['xb_nav_contact_netease_url']));
        update_option('xb_nav_contact_xiaohongshu_qr', esc_url_raw($_POST['xb_nav_contact_xiaohongshu_qr']));
        update_option('xb_nav_contact_douyin_qr', esc_url_raw($_POST['xb_nav_contact_douyin_qr']));
        update_option('xb_nav_contact_wechat_qr', esc_url_raw($_POST['xb_nav_contact_wechat_qr']));
        update_option('xb_nav_iconfont_url', xb_sanitize_iconfont_url($_POST['xb_nav_iconfont_url']));
        echo '<div class="updated"><p>设置已保存！</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>小半自定义网址导航设置</h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="xb_nav_page_title">前台页面标题</label></th>
                    <td>
                        <input type="text" name="xb_nav_page_title" id="xb_nav_page_title" value="<?php echo esc_attr(get_option('xb_nav_page_title', '自定义导航页面')); ?>" class="regular-text" />
                        <p class="description">设置前台页面的标题。</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">显示页面标题</th>
                    <td>
                        <input type="checkbox" name="xb_nav_show_page_title" value="1" <?php checked(get_option('xb_nav_show_page_title', '1'), '1'); ?> />
                        <label>在前台显示页面标题</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="xb_nav_background_color">页面背景颜色</label></th>
                    <td>
                        <input type="text" name="xb_nav_background_color" id="xb_nav_background_color" value="<?php echo esc_attr(get_option('xb_nav_background_color', 'linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%)')); ?>" class="regular-text" />
                        <p class="description">支持 CSS 背景颜色格式，例如 linear-gradient(45deg, #6a11cb, #2575fc) 或 #ffffff。</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">启用数字时钟</th>
                    <td>
                        <input type="checkbox" name="xb_nav_enable_clock" value="1" <?php checked(get_option('xb_nav_enable_clock', '0'), '1'); ?> />
                        <label>在前台右侧显示数字时钟</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">启用 LyToday-JS 热搜榜</th>
                    <td>
                        <input type="checkbox" name="xb_nav_enable_lytoday" value="1" <?php checked(get_option('xb_nav_enable_lytoday', '0'), '1'); ?> />
                        <label>在前台底部显示 LyToday-JS 热搜榜，具体查看https://doc.lylme.com/spage/#/lytoday-js</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="xb_nav_iconfont_url">阿里Iconfont图标样式链接</label></th>
                    <td>
                        <input type="text" name="xb_nav_iconfont_url" id="xb_nav_iconfont_url" value="<?php echo esc_attr(get_option('xb_nav_iconfont_url', '')); ?>" class="regular-text" />
                        <p class="description">输入链接，例如：//at.alicdn.com/t/c/font_4815672_l8426erg1w.css 如果你不自己修改插件源码，就需要指定名称具体看网站教程</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="xb_nav_contact_qq">QQ号</label></th>
                    <td>
                        <input type="text" name="xb_nav_contact_qq" id="xb_nav_contact_qq" value="<?php echo esc_attr(get_option('xb_nav_contact_qq', '')); ?>" class="regular-text" />
                        <p class="description">输入 QQ 号码，留空则不显示。</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="xb_nav_contact_city">城市</label></th>
                    <td>
                        <input type="text" name="xb_nav_contact_city" id="xb_nav_contact_city" value="<?php echo esc_attr(get_option('xb_nav_contact_city', '')); ?>" class="regular-text" />
                        <p class="description">输入城市名称，留空则不显示。</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="xb_nav_contact_email">邮箱</label></th>
                    <td>
                        <input type="email" name="xb_nav_contact_email" id="xb_nav_contact_email" value="<?php echo esc_attr(get_option('xb_nav_contact_email', '')); ?>" class="regular-text" />
                        <p class="description">输入邮箱地址，留空则不显示。</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="xb_nav_contact_bilibili_url">B站链接</label></th>
                    <td>
                        <input type="url" name="xb_nav_contact_bilibili_url" id="xb_nav_contact_bilibili_url" value="<?php echo esc_attr(get_option('xb_nav_contact_bilibili_url', '')); ?>" class="regular-text" />
                        <p class="description">输入 B站主页链接，留空则不显示。</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="xb_nav_contact_bilibili_text">B站链接文本</label></th>
                    <td>
                        <input type="text" name="xb_nav_contact_bilibili_text" id="xb_nav_contact_bilibili_text" value="<?php echo esc_attr(get_option('xb_nav_contact_bilibili_text', '')); ?>" class="regular-text" />
                        <p class="description">输入 B站链接显示的文本，留空则显示“B站”。</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="xb_nav_contact_weibo_url">微博链接</label></th>
                    <td>
                        <input type="url" name="xb_nav_contact_weibo_url" id="xb_nav_contact_weibo_url" value="<?php echo esc_attr(get_option('xb_nav_contact_weibo_url', '')); ?>" class="regular-text" />
                        <p class="description">输入微博主页链接，留空则不显示。</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="xb_nav_contact_netease_url">网易云音乐链接</label></th>
                    <td>
                        <input type="url" name="xb_nav_contact_netease_url" id="xb_nav_contact_netease_url" value="<?php echo esc_attr(get_option('xb_nav_contact_netease_url', '')); ?>" class="regular-text" />
                        <p class="description">输入网易云音乐主页链接，留空则不显示。</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="xb_nav_contact_xiaohongshu_qr">小红书二维码图片地址</label></th>
                    <td>
                        <input type="url" name="xb_nav_contact_xiaohongshu_qr" id="xb_nav_contact_xiaohongshu_qr" value="<?php echo esc_attr(get_option('xb_nav_contact_xiaohongshu_qr', '')); ?>" class="regular-text" />
                        <p class="description">输入小红书二维码图片地址，留空则不显示。</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="xb_nav_contact_douyin_qr">抖音二维码图片地址</label></th>
                    <td>
                        <input type="url" name="xb_nav_contact_douyin_qr" id="xb_nav_contact_douyin_qr" value="<?php echo esc_attr(get_option('xb_nav_contact_douyin_qr', '')); ?>" class="regular-text" />
                        <p class="description">输入抖音二维码图片地址，留空则不显示。</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="xb_nav_contact_wechat_qr">微信二维码图片地址</label></th>
                    <td>
                        <input type="url" name="xb_nav_contact_wechat_qr" id="xb_nav_contact_wechat_qr" value="<?php echo esc_attr(get_option('xb_nav_contact_wechat_qr', '')); ?>" class="regular-text" />
                        <p class="description">输入微信二维码图片地址，留空则不显示。</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="xb_nav_announcement">公告内容</label></th>
                    <td>
                        <textarea name="xb_nav_announcement" id="xb_nav_announcement" rows="5" class="large-text"><?php echo esc_textarea(get_option('xb_nav_announcement', '')); ?></textarea>
                        <p class="description">支持 HTML 格式，留空则不显示。</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="xb_nav_ad_content">广告内容</label></th>
                    <td>
                        <textarea name="xb_nav_ad_content" id="xb_nav_ad_content" rows="5" class="large-text"><?php echo esc_textarea(get_option('xb_nav_ad_content', '')); ?></textarea>
                        <p class="description">支持 HTML 格式，留空则不显示。建议图片尺寸为 300x200px。</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">显示导航数量</th>
                    <td>
                        <input type="checkbox" name="xb_nav_show_count" value="1" <?php checked(get_option('xb_nav_show_count', '0'), '1'); ?> />
                        <label>在前台显示当前收录的导航数量</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="xb_nav_apply_url">申请收录网址</label></th>
                    <td>
                        <input type="url" name="xb_nav_apply_url" id="xb_nav_apply_url" value="<?php echo esc_attr(get_option('xb_nav_apply_url', '')); ?>" class="regular-text" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">显示申请收录</th>
                    <td>
                        <input type="checkbox" name="xb_nav_show_apply" value="1" <?php checked(get_option('xb_nav_show_apply', '0'), '1'); ?> />
                        <label>在前台显示申请收录入口</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">显示文章</th>
                    <td>
                        <input type="checkbox" name="xb_nav_show_posts" value="1" <?php checked(get_option('xb_nav_show_posts', '0'), '1'); ?> />
                        <label>在前台显示文章</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">文章类型</th>
                    <td>
                        <select name="xb_nav_post_type">
                            <option value="recent" <?php selected(get_option('xb_nav_post_type', 'recent'), 'recent'); ?>>最新文章</option>
                            <option value="random" <?php selected(get_option('xb_nav_post_type', 'recent'), 'random'); ?>>随机文章</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="xb_nav_post_count">文章数量</label></th>
                    <td>
                        <input type="number" name="xb_nav_post_count" id="xb_nav_post_count" value="<?php echo esc_attr(get_option('xb_nav_post_count', 3)); ?>" min="1" max="10" />
                    </td>
                </tr>
                <tr>
                    <th scope="row">显示导航描述</th>
                    <td>
                        <input type="checkbox" name="xb_nav_show_desc" value="1" <?php checked(get_option('xb_nav_show_desc', '0'), '1'); ?> />
                        <label>在鼠标悬停时显示导航的完整描述</label>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="xb_nav_settings_submit" class="button-primary" value="保存设置" />
            </p>
        </form>
    </div>
    <?php
}

// 添加导航页面
function xb_nav_add_page() {
    global $wpdb;
    $table_nav = $wpdb->prefix . 'xb_nav';
    $table_category = $wpdb->prefix . 'xb_nav_categories';

    $message = '';
    $message_type = 'updated';

    // 处理分类添加
    if (isset($_POST['xb_nav_add_category'])) {
        $category_name = sanitize_text_field($_POST['category_name']);
        $category_icon = esc_url_raw($_POST['category_icon']);

        if (empty($category_name)) {
            $message = '分类名称为必填项！';
            $message_type = 'error';
        } elseif (empty($category_icon) || !xb_validate_url($category_icon)) {
            $message = '分类图标地址无效！';
            $message_type = 'error';
        } elseif (strlen($category_name) > 255 || strlen($category_icon) > 255) {
            $message = '分类名称或图标地址过长！';
            $message_type = 'error';
        } else {
            try {
                $wpdb->query('START TRANSACTION');
                $result = $wpdb->insert(
                    $table_category,
                    array(
                        'name' => $category_name,
                        'icon' => $category_icon,
                    ),
                    array('%s', '%s')
                );

                if ($result === false) {
                    throw new Exception('数据库错误：' . $wpdb->last_error);
                }
                $wpdb->query('COMMIT');
                $message = '分类已添加！';
            } catch (Exception $e) {
                $wpdb->query('ROLLBACK');
                $message = '添加分类失败：' . esc_html($e->getMessage());
                $message_type = 'error';
            }
        }
    }

    // 处理分类编辑
    if (isset($_POST['xb_nav_edit_category'])) {
        $category_id = intval($_POST['category_id']);
        $category_name = sanitize_text_field($_POST['category_name']);
        $category_icon = esc_url_raw($_POST['category_icon']);

        if (empty($category_name)) {
            $message = '分类名称为必填项！';
            $message_type = 'error';
        } elseif (empty($category_icon) || !xb_validate_url($category_icon)) {
            $message = '分类图标地址无效！';
            $message_type = 'error';
        } elseif (strlen($category_name) > 255 || strlen($category_icon) > 255) {
            $message = '分类名称或图标地址过长！';
            $message_type = 'error';
        } else {
            try {
                $wpdb->query('START TRANSACTION');
                $result = $wpdb->update(
                    $table_category,
                    array(
                        'name' => $category_name,
                        'icon' => $category_icon,
                    ),
                    array('id' => $category_id),
                    array('%s', '%s'),
                    array('%d')
                );

                if ($result === false) {
                    throw new Exception('数据库错误：' . $wpdb->last_error);
                }
                $wpdb->query('COMMIT');
                $message = '分类已更新！';
            } catch (Exception $e) {
                $wpdb->query('ROLLBACK');
                $message = '更新分类失败：' . esc_html($e->getMessage());
                $message_type = 'error';
            }
        }
    }

    // 处理导航添加
    if (isset($_POST['xb_nav_add_item'])) {
        $nav_name = sanitize_text_field($_POST['nav_name']);
        $nav_url = esc_url_raw($_POST['nav_url']);
        $nav_icon = esc_url_raw($_POST['nav_icon']);
        $nav_description = sanitize_textarea_field($_POST['nav_description']);
        $nav_intro_url = esc_url_raw($_POST['nav_intro_url']);
        $nav_category = intval($_POST['nav_category']);
        $redirect_to_intro = isset($_POST['redirect_to_intro']) ? 1 : 0;

        // 验证必填字段
        if (empty($nav_name)) {
            $message = '导航名称为必填项！';
            $message_type = 'error';
        } elseif (empty($nav_url) || !xb_validate_url($nav_url)) {
            $message = '导航地址无效！';
            $message_type = 'error';
        } elseif (empty($nav_icon) || !xb_validate_url($nav_icon)) {
            $message = '导航图标地址无效！';
            $message_type = 'error';
        } elseif (empty($nav_description)) {
            $message = '导航描述为必填项！';
            $message_type = 'error';
        } elseif (empty($nav_intro_url) || !xb_validate_url($nav_intro_url)) {
            $message = '导航介绍地址无效！';
            $message_type = 'error';
        } elseif (empty($nav_category)) {
            $message = '请选择导航分类！';
            $message_type = 'error';
        } elseif (strlen($nav_name) > 255 || strlen($nav_url) > 255 || strlen($nav_icon) > 255 || strlen($nav_intro_url) > 255) {
            $message = '导航名称、地址、图标或介绍地址过长！';
            $message_type = 'error';
        } else {
            // 验证分类是否存在
            $category_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_category WHERE id = %d", $nav_category));
            if (!$category_exists) {
                $message = '选择的分类不存在！';
                $message_type = 'error';
            } else {
                try {
                    $wpdb->query('START TRANSACTION');
                    $result = $wpdb->insert(
                        $table_nav,
                        array(
                            'name' => $nav_name,
                            'url' => $nav_url,
                            'icon' => $nav_icon,
                            'description' => $nav_description,
                            'intro_url' => $nav_intro_url,
                            'category_id' => $nav_category,
                            'redirect_to_intro' => $redirect_to_intro,
                        ),
                        array('%s', '%s', '%s', '%s', '%s', '%d', '%d')
                    );

                    if ($result === false) {
                        throw new Exception('数据库错误：' . $wpdb->last_error);
                    }
                    $wpdb->query('COMMIT');
                    $message = '导航已添加！';
                } catch (Exception $e) {
                    $wpdb->query('ROLLBACK');
                    $message = '添加导航失败：' . esc_html($e->getMessage());
                    $message_type = 'error';
                }
            }
        }
    }

    // 获取所有分类及其导航数量
    $categories = $wpdb->get_results("
        SELECT c.*, COUNT(n.id) as nav_count
        FROM $table_category c
        LEFT JOIN $table_nav n ON c.id = n.category_id
        GROUP BY c.id
        ORDER BY c.id ASC
    ");

    // AJAX 相关参数
    $ajax_url = admin_url('admin-ajax.php');
    $nonce = wp_create_nonce('xb_nav_nonce');
    ?>
    <div class="wrap">
        <h1>添加网址导航</h1>
        <?php if (!empty($message)): ?>
            <div class="<?php echo esc_attr($message_type); ?>">
                <p><?php echo esc_html($message); ?></p>
            </div>
        <?php endif; ?>

        <!-- 添加分类 -->
        <h2>添加网址导航分类</h2>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="category_name">分类名称</label></th>
                    <td>
                        <input type="text" name="category_name" id="category_name" class="regular-text" required />
                        <p class="description">请输入分类名称，例如“常用工具”。</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="category_icon">分类图标地址</label></th>
                    <td>
                        <input type="url" name="category_icon" id="category_icon" class="regular-text" required />
                        <p class="description">请输入分类图标的URL，例如“https://example.com/icon.png”。</p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <input type="submit" name="xb_nav_add_category" class="button-primary" value="添加分类" />
            </p>
        </form>

        <!-- 分类列表 -->
        <h2>已添加的网址导航分类</h2>
        <?php if (empty($categories)): ?>
            <p>暂无网址导航分类。</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col">分类名称</th>
                        <th scope="col">图标</th>
                        <th scope="col">数量</th>
                        <th scope="col">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo esc_html($category->name); ?></td>
                            <td><img src="<?php echo esc_url($category->icon); ?>" alt="<?php echo esc_attr($category->name); ?>" style="width: 32px; height: 32px;" /></td>
                            <td><?php echo intval($category->nav_count); ?> 个</td>
                            <td>
                                <button class="xb-category-edit button" data-id="<?php echo esc_attr($category->id); ?>" data-name="<?php echo esc_attr($category->name); ?>" data-icon="<?php echo esc_attr($category->icon); ?>">编辑</button>
                                <button class="xb-category-delete button" data-id="<?php echo esc_attr($category->id); ?>" <?php echo $category->nav_count > 0 ? 'disabled' : ''; ?>>删除</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- 编辑分类模态框 -->
        <div id="xb-category-edit-modal" class="xb-modal" style="display:none;">
            <div class="xb-modal-content">
                <span class="xb-modal-close">×</span>
                <h2>编辑导航分类</h2>
                <form id="xb-category-edit-form" method="post">
                    <input type="hidden" name="category_id" id="edit-category-id" />
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="edit-category-name">分类名称</label></th>
                            <td>
                                <input type="text" name="category_name" id="edit-category-name" class="regular-text" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="edit-category-icon">分类图标地址</label></th>
                            <td>
                                <input type="url" name="category_icon" id="edit-category-icon" class="regular-text" required />
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="xb_nav_edit_category" class="button-primary" value="保存更改" />
                    </p>
                </form>
            </div>
        </div>

        <!-- 添加导航 -->
        <h2>添加网址导航内容</h2>
        <?php if (empty($categories)): ?>
            <div class="error">
                <p>请先添加至少一个网址导航分类！</p>
            </div>
        <?php else: ?>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="nav_name">网址导航名称</label></th>
                        <td>
                            <input type="text" name="nav_name" id="nav_name" class="regular-text" required />
                            <p class="description">请输入网址名称，例如“无忌AI”。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="nav_url">网址导航地址</label></th>
                        <td>
                            <input type="url" name="nav_url" id="nav_url" class="regular-text" required />
                            <p class="description">请输入网址的URL，例如“https://ai.wujiit.com”。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="nav_icon">网址导航图标地址</label></th>
                        <td>
                            <input type="url" name="nav_icon" id="nav_icon" class="regular-text" required />
                            <p class="description">请输入网址导航图标的URL，例如“https://example.com/google-icon.png”。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="nav_description">网址导航描述</label></th>
                        <td>
                            <textarea name="nav_description" id="nav_description" rows="5" class="large-text" required></textarea>
                            <p class="description">请输入网址导航的描述，例如“免费的AI图像处理平台”。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="nav_intro_url">网址导航介绍地址</label></th>
                        <td>
                            <input type="url" name="nav_intro_url" id="nav_intro_url" class="regular-text" required />
                            <p class="description">请输入网址导航介绍页的URL，例如“https://example.com/wujiai”。不介绍就随便填写个网址。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">跳转设置</th>
                        <td>
                            <input type="checkbox" name="redirect_to_intro" id="redirect_to_intro" value="1" />
                            <label for="redirect_to_intro">点击时先跳转到网址导航介绍地址</label>
                            <p class="description">勾选后，点击前台导航将先跳转到介绍地址，否则跳转到网址导航地址。</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="nav_category">网址导航分类</label></th>
                        <td>
                            <select name="nav_category" id="nav_category" required>
                                <option value="">请选择分类</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo esc_attr($category->id); ?>"><?php echo esc_html($category->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">请选择导航所属的分类。</p>
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="xb_nav_add_item" class="button-primary" value="添加网址导航" />
                </p>
            </form>
        <?php endif; ?>
    </div>

    <!-- 模态框样式 -->
    <style>
        .xb-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .xb-modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        .xb-modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
        }
    </style>

    <!-- 嵌入 JS -->
    <script>
        jQuery(document).ready(function($) {
            // 编辑分类
            $(document).on('click', '.xb-category-edit', function() {
                const id = $(this).data('id');
                const name = $(this).data('name');
                const icon = $(this).data('icon');
                $('#edit-category-id').val(id);
                $('#edit-category-name').val(name);
                $('#edit-category-icon').val(icon);
                $('#xb-category-edit-modal').show();
            });

            // 删除分类
            $(document).on('click', '.xb-category-delete', function() {
                if (!confirm('确定删除此分类？')) return;
                const id = $(this).data('id');
                $.ajax({
                    url: '<?php echo esc_url($ajax_url); ?>',
                    method: 'POST',
                    data: {
                        action: 'xb_nav_delete_category',
                        id: id,
                        nonce: '<?php echo esc_js($nonce); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert(response.data.message || '删除失败。');
                        }
                    },
                    error: function() {
                        alert('请求失败，请检查网络。');
                    }
                });
            });

            // 关闭模态框
            $('.xb-modal-close').on('click', function() {
                $('#xb-category-edit-modal').hide();
            });
            $(document).on('click', '.xb-modal', function(e) {
                if ($(e.target).hasClass('xb-modal')) {
                    $('#xb-category-edit-modal').hide();
                }
            });
        });
    </script>
    <?php
}

// 导航管理页面
function xb_nav_manage_page() {
    global $wpdb;
    $table_nav = $wpdb->prefix . 'xb_nav';
    $table_category = $wpdb->prefix . 'xb_nav_categories';

    // 处理编辑
    if (isset($_POST['xb_nav_edit_item'])) {
        $nav_id = intval($_POST['nav_id']);
        $nav_name = sanitize_text_field($_POST['nav_name']);
        $nav_url = esc_url_raw($_POST['nav_url']);
        $nav_icon = esc_url_raw($_POST['nav_icon']);
        $nav_description = sanitize_textarea_field($_POST['nav_description']);
        $nav_intro_url = esc_url_raw($_POST['nav_intro_url']);
        $nav_category = intval($_POST['nav_category']);
        $redirect_to_intro = isset($_POST['redirect_to_intro']) ? 1 : 0;

        if (empty($nav_name)) {
            echo '<div class="error"><p>网址导航名称为必填项！</p></div>';
        } elseif (empty($nav_url) || !xb_validate_url($nav_url)) {
            echo '<div class="error"><p>网址导航地址无效！</p></div>';
        } elseif (empty($nav_icon) || !xb_validate_url($nav_icon)) {
            echo '<div class="error"><p>网址导航图标地址无效！</p></div>';
        } elseif (empty($nav_description)) {
            echo '<div class="error"><p>网址导航描述为必填项！</p></div>';
        } elseif (empty($nav_intro_url) || !xb_validate_url($nav_intro_url)) {
            echo '<div class="error"><p>网址导航介绍地址无效！</p></div>';
        } elseif (empty($nav_category)) {
            echo '<div class="error"><p>请选择网址导航分类！</p></div>';
        } elseif (strlen($nav_name) > 255 || strlen($nav_url) > 255 || strlen($nav_icon) > 255 || strlen($nav_intro_url) > 255) {
            echo '<div class="error"><p>网址导航名称、地址、图标或介绍地址过长！</p></div>';
        } else {
            $category_exists = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_category WHERE id = %d", $nav_category));
            if (!$category_exists) {
                echo '<div class="error"><p>选择的分类不存在！</p></div>';
            } else {
                try {
                    $wpdb->query('START TRANSACTION');
                    $result = $wpdb->update(
                        $table_nav,
                        array(
                            'name' => $nav_name,
                            'url' => $nav_url,
                            'icon' => $nav_icon,
                            'description' => $nav_description,
                            'intro_url' => $nav_intro_url,
                            'category_id' => $nav_category,
                            'redirect_to_intro' => $redirect_to_intro,
                        ),
                        array('id' => $nav_id),
                        array('%s', '%s', '%s', '%s', '%s', '%d', '%d'),
                        array('%d')
                    );

                    if ($result === false) {
                        throw new Exception('数据库错误：' . $wpdb->last_error);
                    }
                    $wpdb->query('COMMIT');
                    echo '<div class="updated"><p>导航已更新！</p></div>';
                } catch (Exception $e) {
                    $wpdb->query('ROLLBACK');
                    echo '<div class="error"><p>更新导航失败：' . esc_html($e->getMessage()) . '</p></div>';
                }
            }
        }
    }

    // 获取所有分类
    $categories = $wpdb->get_results("SELECT * FROM $table_category ORDER BY id ASC");

    // AJAX 相关参数
    $ajax_url = admin_url('admin-ajax.php');
    $nonce = wp_create_nonce('xb_nav_nonce');
    ?>
    <div class="wrap">
        <h1>导航管理</h1>

        <!-- 导航列表 -->
        <h2>导航列表</h2>
        <div id="xb-nav-list">
            <!-- AJAX 加载导航列表 -->
        </div>
        <div id="xb-nav-pagination"></div>

        <!-- 编辑导航模态框 -->
        <div id="xb-nav-edit-modal" class="xb-modal" style="display:none;">
            <div class="xb-modal-content">
                <span class="xb-modal-close">×</span>
                <h2>编辑网址导航</h2>
                <form id="xb-nav-edit-form" method="post">
                    <input type="hidden" name="nav_id" id="edit-nav-id" />
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="edit-nav-name">网址导航名称</label></th>
                            <td>
                                <input type="text" name="nav_name" id="edit-nav-name" class="regular-text" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="edit-nav-url">网址导航地址</label></th>
                            <td>
                                <input type="url" name="nav_url" id="edit-nav-url" class="regular-text" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="edit-nav-icon">网址导航图标地址</label></th>
                            <td>
                                <input type="url" name="nav_icon" id="edit-nav-icon" class="regular-text" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="edit-nav-description">网址导航描述</label></th>
                            <td>
                                <textarea name="nav_description" id="edit-nav-description" rows="5" class="large-text" required></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="edit-nav-intro-url">网址导航介绍地址</label></th>
                            <td>
                                <input type="url" name="nav_intro_url" id="edit-nav-intro-url" class="regular-text" required />
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">跳转设置</th>
                            <td>
                                <input type="checkbox" name="redirect_to_intro" id="edit-redirect-to-intro" value="1" />
                                <label for="edit-redirect-to-intro">点击时先跳转到网址导航介绍地址</label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="edit-nav-category">网址导航分类</label></th>
                            <td>
                                <select name="nav_category" id="edit-nav-category" required>
                                    <option value="">请选择分类</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo esc_attr($category->id); ?>"><?php echo esc_html($category->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <p class="submit">
                        <input type="submit" name="xb_nav_edit_item" class="button-primary" value="保存更改" />
                    </p>
                </form>
            </div>
        </div>
    </div>

    <!-- 模态框样式 -->
    <style>
        .xb-modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .xb-modal-content {
            background: #fff;
            padding: 20px;
            border-radius: 5px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        .xb-modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 24px;
            cursor: pointer;
        }
        #xb-nav-list table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        #xb-nav-list th, #xb-nav-list td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        #xb-nav-list th {
            background: #f4f4f4;
        }
        #xb-nav-list td a {
            word-break: break-all;
        }
        #xb-nav-pagination {
            margin-top: 10px;
            text-align: center;
        }
        #xb-nav-pagination a, #xb-nav-pagination span {
            margin: 0 5px;
            padding: 5px 10px;
            border: 1px solid #ddd;
            border-radius: 3px;
            text-decoration: none;
        }
        #xb-nav-pagination a:hover {
            background: #f0f0f0;
        }
        #xb-nav-pagination .current {
            background: #007bff;
            color: #fff;
            border-color: #007bff;
        }
    </style>

    <!-- 嵌入 JS -->
    <script>
        jQuery(document).ready(function($) {
            function loadNavList(page = 1) {
                $.ajax({
                    url: '<?php echo esc_url($ajax_url); ?>',
                    method: 'POST',
                    data: {
                        action: 'xb_nav_load_list',
                        page: page,
                        nonce: '<?php echo esc_js($nonce); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#xb-nav-list').html(response.data.list);
                            $('#xb-nav-pagination').html(response.data.pagination);
                        } else {
                            $('#xb-nav-list').html('<p>加载失败，请刷新重试。</p>');
                        }
                    },
                    error: function() {
                        $('#xb-nav-list').html('<p>请求失败，请检查网络。</p>');
                    }
                });
            }
            loadNavList();
            $(document).on('click', '#xb-nav-pagination a', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                loadNavList(page);
            });
            $(document).on('click', '.xb-nav-edit', function() {
                const id = $(this).data('id');
                $.ajax({
                    url: '<?php echo esc_url($ajax_url); ?>',
                    method: 'POST',
                    data: {
                        action: 'xb_nav_get_item',
                        id: id,
                        nonce: '<?php echo esc_js($nonce); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            const item = response.data;
                            $('#edit-nav-id').val(item.id);
                            $('#edit-nav-name').val(item.name);
                            $('#edit-nav-url').val(item.url);
                            $('#edit-nav-icon').val(item.icon);
                            $('#edit-nav-description').val(item.description);
                            $('#edit-nav-intro-url').val(item.intro_url);
                            $('#edit-nav-category').val(item.category_id);
                            $('#edit-redirect-to-intro').prop('checked', item.redirect_to_intro == 1);
                            $('#xb-nav-edit-modal').show();
                        } else {
                            alert(response.data.message || '获取导航数据失败。');
                        }
                    }
                });
            });
            $(document).on('click', '.xb-nav-delete', function() {
                if (!confirm('确定删除此导航？')) return;
                const id = $(this).data('id');
                $.ajax({
                    url: '<?php echo esc_url($ajax_url); ?>',
                    method: 'POST',
                    data: {
                        action: 'xb_nav_delete_item',
                        id: id,
                        nonce: '<?php echo esc_js($nonce); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            loadNavList();
                            alert(response.data.message || '删除成功！');
                        } else {
                            alert(response.data.message || '删除失败。');
                        }
                    }
                });
            });
            $('.xb-modal-close').on('click', function() {
                $('#xb-nav-edit-modal').hide();
            });
            $(document).on('click', '.xb-modal', function(e) {
                if ($(e.target).hasClass('xb-modal')) {
                    $('#xb-nav-edit-modal').hide();
                }
            });
        });
    </script>
    <?php
}

// AJAX 处理加载导航列表
add_action('wp_ajax_xb_nav_load_list', 'xb_nav_load_list');
function xb_nav_load_list() {
    check_ajax_referer('xb_nav_nonce', 'nonce');

    global $wpdb;
    $table_nav = $wpdb->prefix . 'xb_nav';
    $table_category = $wpdb->prefix . 'xb_nav_categories';

    $per_page = 20;
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $offset = ($page - 1) * $per_page;

    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_nav");
    $total_pages = ceil($total_items / $per_page);

    $navs = $wpdb->get_results($wpdb->prepare(
        "SELECT n.*, c.name as category_name 
        FROM $table_nav n 
        LEFT JOIN $table_category c ON n.category_id = c.id 
        ORDER BY n.id DESC 
        LIMIT %d OFFSET %d",
        $per_page,
        $offset
    ));

    ob_start();
    if ($navs) {
        ?>
        <table>
            <thead>
                <tr>
                    <th>名称</th>
                    <th>地址</th>
                    <th>分类</th>
                    <th>跳转类型</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($navs as $nav): ?>
                    <tr>
                        <td><?php echo esc_html($nav->name); ?></td>
                        <td><a href="<?php echo esc_url($nav->url); ?>" target="_blank"><?php echo esc_html($nav->url); ?></a></td>
                        <td><?php echo esc_html($nav->category_name ? $nav->category_name : '未分类'); ?></td>
                        <td><?php echo $nav->redirect_to_intro ? '先跳转介绍地址' : '直接跳转'; ?></td>
                        <td>
                            <button class="xb-nav-edit button" data-id="<?php echo esc_attr($nav->id); ?>">编辑</button>
                            <button class="xb-nav-delete button" data-id="<?php echo esc_attr($nav->id); ?>">删除</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    } else {
        echo '<p>暂无导航数据。</p>';
    }
    $list_html = ob_get_clean();

    ob_start();
    if ($total_pages > 1) {
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i == $page) {
                echo "<span class='current'>$i</span>";
            } else {
                echo "<a href='#' data-page='$i'>$i</a>";
            }
        }
    }
    $pagination_html = ob_get_clean();

    wp_send_json_success(array(
        'list' => $list_html,
        'pagination' => $pagination_html,
    ));
}

// AJAX 获取单个导航数据
add_action('wp_ajax_xb_nav_get_item', 'xb_nav_get_item');
function xb_nav_get_item() {
    check_ajax_referer('xb_nav_nonce', 'nonce');

    global $wpdb;
    $table_nav = $wpdb->prefix . 'xb_nav';
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    $item = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_nav WHERE id = %d", $id));
    if ($item) {
        wp_send_json_success($item);
    } else {
        wp_send_json_error(array('message' => '导航不存在。'));
    }
}

// AJAX 删除导航
add_action('wp_ajax_xb_nav_delete_item', 'xb_nav_delete_item');
function xb_nav_delete_item() {
    check_ajax_referer('xb_nav_nonce', 'nonce');

    global $wpdb;
    $table_nav = $wpdb->prefix . 'xb_nav';
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    try {
        $wpdb->query('START TRANSACTION');
        $deleted = $wpdb->delete($table_nav, array('id' => $id), array('%d'));
        if ($deleted === false) {
            throw new Exception('数据库错误：' . $wpdb->last_error);
        }
        $wpdb->query('COMMIT');
        wp_send_json_success(array('message' => '导航已删除。'));
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(array('message' => '删除失败：' . esc_html($e->getMessage())));
    }
}

// AJAX 删除分类
add_action('wp_ajax_xb_nav_delete_category', 'xb_nav_delete_category');
function xb_nav_delete_category() {
    check_ajax_referer('xb_nav_nonce', 'nonce');

    global $wpdb;
    $table_category = $wpdb->prefix . 'xb_nav_categories';
    $table_nav = $wpdb->prefix . 'xb_nav';
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

    // 检查分类下是否有导航
    $nav_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_nav WHERE category_id = %d", $id));
    if ($nav_count > 0) {
        wp_send_json_error(array('message' => '此分类下有导航，无法删除。'));
    }

    try {
        $wpdb->query('START TRANSACTION');
        $deleted = $wpdb->delete($table_category, array('id' => $id), array('%d'));
        if ($deleted === false) {
            throw new Exception('数据库错误：' . $wpdb->last_error);
        }
        $wpdb->query('COMMIT');
        wp_send_json_success(array('message' => '分类已删除。'));
    } catch (Exception $e) {
        $wpdb->query('ROLLBACK');
        wp_send_json_error(array('message' => '删除失败：' . esc_html($e->getMessage())));
    }
}

// 插件卸载时删除相关设置项
register_deactivation_hook(__FILE__, 'xb_nav_deactivate');
function xb_nav_deactivate() {
    // 删除插件相关的选项（仅限以xb_nav_开头的选项）
    $options = array(
        'xb_nav_announcement',
        'xb_nav_ad_content',
        'xb_nav_show_count',
        'xb_nav_apply_url',
        'xb_nav_show_apply',
        'xb_nav_show_posts',
        'xb_nav_post_type',
        'xb_nav_post_count',
        'xb_nav_page_title',
        'xb_nav_show_page_title',
        'xb_nav_background_color',
        'xb_nav_show_desc',
        'xb_nav_enable_clock',
        'xb_nav_enable_lytoday',
        'xb_nav_contact_qq',
        'xb_nav_contact_city',
        'xb_nav_contact_email',
        'xb_nav_contact_bilibili_url',
        'xb_nav_contact_bilibili_text',
        'xb_nav_contact_weibo_url',
        'xb_nav_contact_netease_url',
        'xb_nav_contact_xiaohongshu_qr',
        'xb_nav_contact_douyin_qr',
        'xb_nav_contact_wechat_qr',
        'xb_nav_iconfont_url'
    );
    
    foreach ($options as $option) {
        delete_option($option);
    }
}
?>

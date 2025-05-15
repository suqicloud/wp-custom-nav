(function($, window, document) {
    'use strict';

    const XBNav = {
        /**
         * 初始化函数，绑定所有事件
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * 绑定所有点击、键盘和鼠标悬停事件
         */
        bindEvents: function() {
            $(document)
                .on('click', '#xb-search-btn', this.handleSearch.bind(this))
                .on('keypress', '#xb-search-input', this.handleSearchKeypress.bind(this))
                .on('click', '.xb-search-tab', this.handleSearchTab.bind(this))
                .on('click', '.xb-category-item', this.handleCategoryFilter.bind(this))
                .on('mouseenter mouseleave', '.xb-nav-item', this.handleNavDesc.bind(this))
                .on('mouseenter mouseleave', '.xb-contact-qr', this.handleContactQr.bind(this))
                .on('click', '.xb-hotlist-tab', this.handleHotlistTab.bind(this))
                .on('click', '.xb-nav-item, .xb-hotlist-link', this.handleNavClick.bind(this))
                .on('click', '.xb-nav-edit', this.handleNavEdit.bind(this));
        },

        /**
         * 处理搜索按钮点击事件，根据选择的搜索引擎跳转
         */
        handleSearch: function() {
            const keyword = $('#xb-search-input').val().trim();
            if (!keyword) {
                this.showToast('请输入关键词！');
                return;
            }

            const engine = $('.xb-search-tab.active').data('engine');
            let url = '';
            switch (engine) {
                case 'baidu':
                    url = `https://www.baidu.com/s?wd=${encodeURIComponent(keyword)}`;
                    break;
                case 'google':
                    url = `https://www.google.com/search?q=${encodeURIComponent(keyword)}`;
                    break;
                case 'github':
                    url = `https://github.com/search?q=${encodeURIComponent(keyword)}`;
                    break;
                case 'bilibili':
                    url = `https://search.bilibili.com/all?keyword=${encodeURIComponent(keyword)}`;
                    break;
                case 'weibo':
                    url = `https://s.weibo.com/weibo?q=${encodeURIComponent(keyword)}`;
                    break;
                case 'zhihu':
                    url = `https://www.zhihu.com/search?q=${encodeURIComponent(keyword)}`;
                    break;
                case 'taobao':
                    url = `https://s.taobao.com/search?q=${encodeURIComponent(keyword)}`;
                    break;
                case 'site':
                    url = `${window.location.origin}/?s=${encodeURIComponent(keyword)}`;
                    break;
                default:
                    url = `https://www.baidu.com/s?wd=${encodeURIComponent(keyword)}`;
            }
            window.open(url, '_blank');
        },

        /**
         * 处理搜索框回车事件，触发搜索按钮
         */
        handleSearchKeypress: function(e) {
            if (e.which === 13) {
                $('#xb-search-btn').trigger('click');
            }
        },

        /**
         * 处理搜索选项卡切换，更新激活状态
         */
        handleSearchTab: function(e) {
            $('.xb-search-tab').removeClass('active');
            $(e.currentTarget).addClass('active');
        },

        /**
         * 处理分类过滤，显示对应分类的导航，并保持推荐导航板块显示
         */
        handleCategoryFilter: function(e) {
            const categoryId = $(e.currentTarget).data('category-id');
            $('.xb-nav-section').hide();
            $(`#category-${categoryId}`).show();
            $('.xb-recommended-section').show(); // 始终显示推荐导航板块
            $('.xb-mobile-menu-content').removeClass('active');
        },

        /**
         * 处理导航描述的显示和隐藏（鼠标悬停）
         */
        handleNavDesc: function(e) {
            if (xbNavAjax.show_desc !== '1') return;

            const $item = $(e.currentTarget);
            const $desc = $item.find('.xb-nav-desc-full');

            if (e.type === 'mouseenter') {
                $desc.show();
            } else {
                $desc.hide();
            }
        },

        /**
         * 处理联系方式二维码的显示和隐藏（鼠标悬停）
         */
        handleContactQr: function(e) {
            const $item = $(e.currentTarget);
            const $qrPopup = $item.find('.xb-qr-popup');

            if (e.type === 'mouseenter') {
                $qrPopup.show();
            } else {
                $qrPopup.hide();
            }
        },

        /**
         * 处理热榜选项卡切换，显示对应热榜内容
         */
        handleHotlistTab: function(e) {
            const $tab = $(e.currentTarget);
            const type = $tab.data('type');

            $('.xb-hotlist-tab').removeClass('active');
            $tab.addClass('active');

            $('.xb-hotlist-content').hide();
            $(`#hotlist-${type}`).show();
        },

        /**
         * 处理导航点击，记录点击次数
         */
        handleNavClick: function(e) {
            const $item = $(e.currentTarget);
            const navId = $item.data('nav-id');

            if (navId) {
                $.ajax({
                    url: xbNavAjax.ajax_url,
                    method: 'POST',
                    data: {
                        action: 'xb_nav_record_click',
                        nav_id: navId
                    },
                    success: function() {
                        // 允许默认跳转行为
                    },
                    error: function() {
                        console.log('Failed to record click');
                    }
                });
            }
        },

        /**
         * 处理编辑导航按钮点击，获取导航数据并填充到编辑模态框
         */
        handleNavEdit: function(e) {
            const $button = $(e.currentTarget);
            const id = $button.data('id');
            $.ajax({
                url: xbNavAjax.ajax_url,
                method: 'POST',
                data: {
                    action: 'xb_nav_get_item',
                    id: id,
                    nonce: xbNavAjax.nonce
                },
                success: (response) => {
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
                        $('#edit-nav-order-num').val(item.order_num || 0);
                        $('#edit-is-recommended').prop('checked', item.is_recommended == 1); // 设置推荐状态
                        $('#xb-nav-edit-modal').show();
                    } else {
                        this.showToast(response.data.message || '获取导航数据失败。');
                    }
                },
                error: () => {
                    this.showToast('请求失败，请检查网络。');
                }
            });
        },

        /**
         * 显示提示消息，3秒后自动隐藏
         */
        showToast: function(message) {
            $('#xb-toast-message').text(message);
            $('#xb-toast').fadeIn();
            setTimeout(() => {
                $('#xb-toast').fadeOut();
            }, 3000);
        }
    };

    /**
     * 页面加载完成后初始化 XBNav
     */
    $(document).ready(function() {
        XBNav.init();
    });

})(jQuery, window, document);
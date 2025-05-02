(function($, window, document) {
    'use strict';

    // 模块化命名空间
    const XBNav = {
        // 初始化
        init: function() {
            this.bindEvents();
        },

        // 绑定事件
        bindEvents: function() {
            // 使用事件委托优化性能
            $(document)
                // 搜索按钮点击
                .on('click', '#xb-search-btn', this.handleSearch.bind(this))
                // 搜索框回车
                .on('keypress', '#xb-search-input', this.handleSearchKeypress.bind(this))
                // 切换搜索引擎
                .on('click', '.xb-search-tab', this.handleSearchTab.bind(this))
                // 分类筛选
                .on('click', '.xb-category-item', this.handleCategoryFilter.bind(this))
                // 导航描述显示（根据设置动态绑定）
                .on('mouseenter mouseleave', '.xb-nav-item', this.handleNavDesc.bind(this))
                // 联系方式二维码显示
                .on('mouseenter mouseleave', '.xb-contact-qr', this.handleContactQr.bind(this));
        },

        // 处理搜索
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

        // 处理搜索框回车
        handleSearchKeypress: function(e) {
            if (e.which === 13) {
                $('#xb-search-btn').trigger('click');
            }
        },

        // 处理搜索引擎切换
        handleSearchTab: function(e) {
            $('.xb-search-tab').removeClass('active');
            $(e.currentTarget).addClass('active');
        },

        // 处理分类筛选
        handleCategoryFilter: function(e) {
            const categoryId = $(e.currentTarget).data('category-id');
            $('.xb-nav-section').hide();
            $(`#category-${categoryId}`).show();
            $('.xb-mobile-menu-content').removeClass('active'); // 手机端选择后关闭菜单
        },

        // 处理导航描述显示
        handleNavDesc: function(e) {
            // 检查后台设置是否启用描述显示
            if (xbNavAjax.show_desc !== '1') return;

            const $item = $(e.currentTarget);
            const $desc = $item.find('.xb-nav-desc-full');

            if (e.type === 'mouseenter') {
                $desc.show();
            } else {
                $desc.hide();
            }
        },

        // 处理联系方式二维码显示
        handleContactQr: function(e) {
            const $item = $(e.currentTarget);
            const $qrPopup = $item.find('.xb-qr-popup');

            if (e.type === 'mouseenter') {
                $qrPopup.show();
            } else {
                $qrPopup.hide();
            }
        },

        // 显示提示框
        showToast: function(message) {
            $('#xb-toast-message').text(message);
            $('#xb-toast').fadeIn();
            setTimeout(() => {
                $('#xb-toast').fadeOut();
            }, 2000);
        }
    };

    // 文档就绪时初始化
    $(document).ready(function() {
        XBNav.init();
    });

})(jQuery, window, document);
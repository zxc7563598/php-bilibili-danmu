<link rel="stylesheet" href="/css/bulma.min.css">
<script src="/js/vue.3.5.12.js"></script>
<script src="/js/axios.min.js"></script>
<script src="/js/crypto-js.min.js"></script>
<style>
    html,
    body {
        height: 100%;
        margin: 0;
        padding: 0;
        width: 100%;
        background-color: var(--bulma-scheme-main-bis);
    }

    .navbar {
        padding: 0 1rem;
    }

    /* 侧边栏样式 */
    #sidebar {
        width: 250px;
        transition: transform 0.3s ease-out;

    }

    /* 隐藏状态：使用 transform 将侧边栏滑出屏幕 */
    #sidebar .is-hidden {
        transform: translateX(-100%);
    }

    /* 菜单按钮的样式 */
    #menu-toggle {
        cursor: pointer;
    }

    .menu-container {
        min-height: 70vh;
        background-color: var(--bulma-scheme-main);
    }

    #editor-container {
        border-radius: 0 0 var(--bulma-input-radius) var(--bulma-input-radius);
        padding: 1rem 0;
    }

    #main-content {
        position: relative;
        min-height: 70vh;
    }

    #main-content-body {
        display: none;
    }

    #loading {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: #f0f0f0;
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
    }

    /* 悬浮通知样式 */
    .notification-fixed {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 1000;
        width: 90%;
        max-width: 500px;
    }

    /* 滑入和淡出动画 */
    .slide-fade-enter-active {
        animation: slideIn 0.5s ease-out;
    }

    .slide-fade-leave-active {
        animation: fadeOut 0.5s ease-out;
    }
</style>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // 获取菜单按钮和侧边栏
        const menuToggle = document.getElementById('menu-toggle');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');

        // 检查是否为移动设备（屏幕宽度小于 768px）
        const isMobile = window.matchMedia("(max-width: 1024px)").matches;

        if (isMobile) {
            sidebar.classList.toggle('is-hidden');
        }

        // 点击菜单按钮时切换侧边栏显示状态
        menuToggle.addEventListener('click', () => {
            console.log(isMobile);
            sidebar.classList.toggle('is-hidden');
            if (isMobile) {
                mainContent.classList.toggle('is-hidden');
            }
            adjustContentWidth();
        });

        const adjustContentWidth = () => {
            // 计算菜单显示或隐藏时主体内容的宽度
            if (sidebar.classList.contains('is-hidden')) {
                mainContent.style.width = '100%'; // 菜单隐藏时，内容占满 100% 宽度
            } else {
                mainContent.style.width = '80%'; // 菜单显示时，内容区域留出 250px 的空间
            }
        };
        adjustContentWidth();
    });
</script>
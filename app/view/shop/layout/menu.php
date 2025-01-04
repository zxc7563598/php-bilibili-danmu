<div id="sidebar" class="column is-one-fifth">
    <aside class="menu" style="padding:1rem;">
        <p class="menu-label">配置相关</p>
        <ul class="menu-list">
            <li><a href="/points-mall/system-configuration" class="<?php echo ($currentPage === 'system-configuration') ? 'is-active' : ''; ?>">系统配置</a></li>
            <li><a href="/points-mall/mall-configuration" class="<?php echo ($currentPage === 'mall-configuration') ? 'is-active' : ''; ?>">商城配置</a></li>
        </ul>
        <p class="menu-label">商城相关</p>
        <ul class="menu-list">
            <li><a href="/points-mall/user-management" class="<?php echo ($currentPage === 'user-management') ? 'is-active' : ''; ?>">用户管理</a></li>
            <li><a href="/points-mall/product-management" class="<?php echo ($currentPage === 'product-management') ? 'is-active' : ''; ?>">商品管理</a></li>
            <li><a href="/points-mall/shipping-management" class="<?php echo ($currentPage === 'shipping-management') ? 'is-active' : ''; ?>" style="display:flex;">
                    发货管理
                    <?php if ($records > 0) { ?>
                        <span class="tag is-rounded <?php echo ($currentPage === 'shipping-management') ? '' : 'is-light is-danger'; ?>" style="margin-left:auto;"><?php echo $records; ?></span>
                    <?php } ?>
                </a></li>
            <li><a href="/points-mall/complaint-management" class="<?php echo ($currentPage === 'complaint-management') ? 'is-active' : ''; ?>" style="display:flex;">
                    投诉管理
                    <?php if ($complaint > 0) { ?>
                        <span class="tag is-rounded <?php echo ($currentPage === 'complaint-management') ? '' : 'is-light is-danger'; ?>" style="margin-left:auto;"><?php echo $complaint; ?></span>
                    <?php } ?>
                </a></li>
        </ul>
        <p class="menu-label">其他</p>
        <ul class="menu-list">
            <li><a href="https://hejunjie.life/posts/b06795f9.html" class="<?php echo ($currentPage === 'feedback') ? 'is-active' : ''; ?>">救命！我有问题</a></li>
            <li><a href="/">回到控制台</a></li>
        </ul>
    </aside>
</div>
<?php $this->layout('layouts.admin'); ?>

<?php $this->section('title'); ?>Plugins<?php $this->endSection(); ?>

<?php $this->section('content'); ?>

<h1 class="page-title">Plugin Management</h1>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Installed Plugins</h2>
    </div>
    <div class="card-body">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>ID</th>
                    <th>Version</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($plugins as $plugin): ?>
                    <?php
                    $isActive = $pluginManager->isActive($plugin->getId());
                    $config = $pluginManager->getPluginConfig($plugin->getId());
                    ?>
                    <tr>
                        <td>
                            <strong><?= $this->e($plugin->getName()) ?></strong>
                            <?php if (!empty($config['description'])): ?>
                                <br><small style="color: #666;"><?= $this->e($config['description']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><code><?= $this->e($plugin->getId()) ?></code></td>
                        <td><?= $this->e($plugin->getVersion()) ?></td>
                        <td>
                            <?php if ($isActive): ?>
                                <span class="badge badge-success">Active</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($isActive): ?>
                                <form method="post" action="/admin/plugins/<?= $this->e($plugin->getId()) ?>/deactivate" style="display: inline;">
                                    <button type="submit" class="btn btn-danger btn-sm">Deactivate</button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="/admin/plugins/<?= $this->e($plugin->getId()) ?>/activate" style="display: inline;">
                                    <button type="submit" class="btn btn-primary btn-sm">Activate</button>
                                </form>
                            <?php endif; ?>

                            <a href="/admin/plugins/<?= $this->e($plugin->getId()) ?>/settings" class="btn btn-secondary btn-sm">Settings</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php $this->endSection(); ?>
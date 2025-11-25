<?php $this->layout('layouts.admin'); ?>

<?php $this->section('title'); ?>
<?= $this->e($plugin->getName()) ?> Settings
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>

<h1 class="page-title"><?= $this->e($plugin->getName()) ?> Settings</h1>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Plugin Configuration</h2>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            Plugin ID: <strong><?= $this->e($plugin->getId()) ?></strong><br>
            Version: <strong><?= $this->e($plugin->getVersion()) ?></strong><br>
            Status: <strong><?= $pluginManager->isActive($plugin->getId()) ? 'Active' : 'Inactive' ?></strong>
        </div>

        <h3 style="margin-bottom: 20px;">Settings (JSON)</h3>

        <form method="post" action="/admin/plugins/<?= $this->e($plugin->getId()) ?>/settings">
            <div class="form-group">
                <label class="form-label">Configuration (JSON format)</label>
                <textarea name="config" class="form-control" rows="15"><?= $this->e(json_encode($config['settings'] ?? [], JSON_PRETTY_PRINT)) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">Save Settings</button>
            <a href="/admin/plugins" class="btn btn-secondary">Back to Plugins</a>
        </form>
    </div>
</div>

<?php $this->endSection(); ?>
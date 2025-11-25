<?php $this->layout('layouts.admin'); ?>

<?php $this->section('title'); ?>Dashboard<?php $this->endSection(); ?>

<?php $this->section('content'); ?>

<h1 class="page-title">Dashboard</h1>

<div class="stats-grid">
    <div class="stat-card">
        <h3><?= $totalPlugins ?></h3>
        <p>Total Plugins</p>
    </div>

    <div class="stat-card">
        <h3><?= $activePlugins ?></h3>
        <p>Active Plugins</p>
    </div>

    <div class="stat-card">
        <h3><?= count($navItems) ?></h3>
        <p>Navigation Items</p>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Quick Actions</h2>
    </div>
    <div class="card-body">
        <a href="/admin/plugins" class="btn btn-primary">Manage Plugins</a>
        <a href="/" class="btn btn-secondary">View Site</a>
    </div>
</div>

<?php $this->endSection(); ?>
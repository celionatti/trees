<?php

declare(strict_types=1);

/*
* ----------------------------------------------
* Admin - routes.php
* ----------------------------------------------
* @package Trees 2025
*/

use Trees\Http\Response;

// Admin Dashboard
$router->get('/admin', function($request, $params, $container) use ($pluginId, $view) {
    
    $pluginManager = $container->get('plugin_manager');
    $hookManager = $container->get('hook_manager');
    
    // Get all plugins
    $allPlugins = $pluginManager->getAllPlugins();
    $activeCount = count($pluginManager->getActivePlugins());
    
    // Get navigation
    $navItems = $hookManager->applyFilters('navigation.items', []);
    
    $html = $view->render('dashboard.index', [
        'totalPlugins' => count($allPlugins),
        'activePlugins' => $activeCount,
        'navItems' => $navItems
    ]);
    
    return Response::html($html);
    
}, $pluginId);

// Plugin Management
$router->get('/admin/plugins', function($request, $params, $container) use ($pluginId, $view) {
    
    $pluginManager = $container->get('plugin_manager');
    $hookManager = $container->get('hook_manager');
    
    $allPlugins = $pluginManager->getAllPlugins();
    $navItems = $hookManager->applyFilters('navigation.items', []);
    
    $html = $view->render('plugins.index', [
        'plugins' => $allPlugins,
        'pluginManager' => $pluginManager,
        'navItems' => $navItems
    ]);
    
    return Response::html($html);
    
}, $pluginId);

// Plugin Settings
$router->get('/admin/plugins/{id}/settings', function($request, $params, $container) use ($pluginId, $view) {
    
    $pluginManager = $container->get('plugin_manager');
    $hookManager = $container->get('hook_manager');
    
    $id = $params[0] ?? null;
    $plugin = $pluginManager->getPlugin($id);
    
    if (!$plugin) {
        return Response::html('Plugin not found')->withStatus(404);
    }
    
    $config = $pluginManager->getPluginConfig($id);
    $navItems = $hookManager->applyFilters('navigation.items', []);
    
    $html = $view->render('plugins.settings', [
        'plugin' => $plugin,
        'config' => $config,
        'navItems' => $navItems
    ]);
    
    return Response::html($html);
    
}, $pluginId);

// Activate Plugin
$router->post('/admin/plugins/{id}/activate', function($request, $params, $container) use ($pluginId) {
    
    $pluginManager = $container->get('plugin_manager');
    $id = $params[0] ?? null;
    
    if ($pluginManager->activate($id)) {
        return Response::redirect('/admin/plugins');
    }
    
    return Response::html('Failed to activate plugin')->withStatus(500);
    
}, $pluginId);

// Deactivate Plugin
$router->post('/admin/plugins/{id}/deactivate', function($request, $params, $container) use ($pluginId) {
    
    $pluginManager = $container->get('plugin_manager');
    $id = $params[0] ?? null;
    
    if ($pluginManager->deactivate($id)) {
        return Response::redirect('/admin/plugins');
    }
    
    return Response::html('Failed to deactivate plugin')->withStatus(500);
    
}, $pluginId);

// Update Plugin Settings
$router->post('/admin/plugins/{id}/settings', function($request, $params, $container) use ($pluginId) {
    
    $pluginManager = $container->get('plugin_manager');
    $id = $params[0] ?? null;
    
    $data = $request->getParsedBody();
    
    if ($pluginManager->updatePluginConfig($id, ['settings' => $data])) {
        return Response::redirect("/admin/plugins/{$id}/settings");
    }
    
    return Response::html('Failed to update settings')->withStatus(500);
    
}, $pluginId);
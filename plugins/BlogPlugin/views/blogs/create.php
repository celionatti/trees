<?php $this->layout('layouts.main'); ?>

<?php $this->section('title'); ?>
Create New Post - Blog
<?php $this->endSection(); ?>

<?php $this->section('content'); ?>
<h1>Create New Blog Post</h1>

<form method="post" action="/blog" style="max-width: 600px;">
    <div style="margin-bottom: 15px;">
        <label for="title" style="display: block; margin-bottom: 5px; font-weight: bold;">Title</label>
        <input
            type="text"
            id="title"
            name="title"
            required
            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
    </div>

    <div style="margin-bottom: 15px;">
        <label for="excerpt" style="display: block; margin-bottom: 5px; font-weight: bold;">Excerpt</label>
        <textarea
            id="excerpt"
            name="excerpt"
            rows="3"
            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
    </div>

    <div style="margin-bottom: 15px;">
        <label for="content" style="display: block; margin-bottom: 5px; font-weight: bold;">Content</label>
        <textarea
            id="content"
            name="content"
            rows="10"
            required
            style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
    </div>

    <div>
        <button
            type="submit"
            style="background: #333; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;">
            Create Post
        </button>
        <a
            href="/blog"
            style="margin-left: 10px; color: #666;">
            Cancel
        </a>
    </div>
</form>
<?php $this->endSection(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php /* title, description, keywords, canonical, og:* from wl_meta_tags (PageMeta); the page's defaults in $page_meta */ ?>
<?php echo seo_head(isset($page_meta) ? $page_meta : array()); ?>
<link rel="shortcut icon" href="<?php echo asset_url('favicon.ico'); ?>">
<link rel="stylesheet" href="<?php echo asset_url('assets/designer/themes/default/css/bootstrap.min.css'); ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="<?php echo asset_url('assets/designer/themes/default/css/conditional_sh.css'); ?>">
<link rel="stylesheet" href="<?php echo asset_url('assets/designer/themes/default/css/customcart.css'); ?>">
</head>
<?php /* $body_class: an extra class on <body> ('' = none); $body_attrs: [name => value] more attributes;
   $is_frame: a popup page inside an iframe (no header / footer; class kt-frame-body) */ ?>
<body<?php echo ! empty($is_frame) ? ' class="kt-frame-body" data-kt-home="'.site_url().'"' : ( ! empty($body_class) ? ' class="'.html_escape($body_class).'"' : ''); ?><?php foreach ( ! empty($body_attrs) ? $body_attrs : array() as $attr => $value) { echo ' '.$attr.'="'.html_escape($value).'"'; } ?>>
<?php if (empty($is_frame)): ?>
<div class="kt-page">
<?php echo view('project_header'); ?>
<?php endif; ?>

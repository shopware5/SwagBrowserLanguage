<?php
// compatibility for older versionsAnchor link for: plugin compatibility for older (<5.2)
namespace Shopware\Components;

if (!interface_exists('\Shopware\Components\CSRFWhitelistAware')) {
    interface CSRFWhitelistAware {}
}
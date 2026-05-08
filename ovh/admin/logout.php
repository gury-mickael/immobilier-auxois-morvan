<?php

declare(strict_types=1);

require dirname(__DIR__) . '/app/bootstrap.php';

cms_logout();
cms_flash('success', 'Déconnexion effectuée.');
cms_redirect('/admin/login');
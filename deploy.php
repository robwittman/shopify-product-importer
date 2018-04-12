<?php
namespace Deployer;

require 'recipe/common.php';

// Project name
set('application', 'my_project');

// Project repository
set('repository', 'git@github.com:robwittman/shopify-product-importer');

// [Optional] Allocate tty for git clone. Default value is false.
set('git_tty', true);

// Shared files/dirs between deploys
set('shared_files', []);
set('shared_dirs', []);

// Writable dirs by web server
set('writable_dirs', []);


// Hosts
host('product-importer.shopify-services.com')
    ->user('deployer')
    ->set('deploy_path', '/var/www');

// Tasks
set('branch', function () {
    return input()->getOption('branch') ?: 'master';
});
set('release_name', function () {
    return date('YmdHis');
});

desc('Deploy your project');
task('deploy', [
    'deploy:info',
    'deploy:prepare',
    'deploy:lock',
    'deploy:release',
    'deploy:update_code',
    'deploy:shared',
    'deploy:writable',
    'deploy:vendors',
    'db:migrate',
    'deploy:supervisor',
    'deploy:clear_paths',
    'deploy:symlink',
    'deploy:unlock',
    'cleanup',
    'success'
]);

task('db:migrate', function() {
    run('cd {{current_path}} && vendor/bin/phinx migrate');
});

task('deploy:supervisor', function() {
    run('crontab {{current_path}}/conf/crontab');
    run('cp {{current_path}}/conf/supervisor/* /usr/supervisor/conf.d/');
    run('supervisorctl reread && supervisorctl update && supervisorctl restart all');
});
// [Optional] If deploy fails automatically unlock.
after('deploy:failed', 'deploy:unlock');

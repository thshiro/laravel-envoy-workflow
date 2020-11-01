@servers(['web' => $user.'@'.$host,'localhost' => '127.0.0.1'])

@setup
    // Sanity checks
    if (!isset($host) || (isset($host) && empty($host))) {
        throw new Exception('ERROR: $host var empty or not defined');
    }
    if (!isset($user) || (isset($user) && empty($user))) {
        throw new Exception('ERROR: $user var empty or not defined');
    }
    if (!isset($deploy_path) || (isset($deploy_path) && empty($deploy_path))) {
        throw new Exception('ERROR: $deploy_path var empty or not defined');
    }
    if (!isset($current_path) || (isset($current_path) && empty($current_path))) {
        throw new Exception('ERROR: $current_path var empty or not defined');
    }
    if (!isset($build) || (isset($build) && empty($build))) {
        throw new Exception('ERROR: $build var empty or not defined');
    }
    if (!isset($commit) || (isset($commit) && empty($commit))) {
        throw new Exception('ERROR: $commit var empty or not defined');
    }
    if (!isset($local_dir) || (isset($local_dir) && empty($local_dir))) {
        throw new Exception('ERROR: $local_dir var empty or not defined');
    }

    if (file_exists($deploy_path) || is_writable($deploy_path)) {
        throw new Exception("ERROR: cannot access $deploy_path");
    }

    // Ensure given $deploy_path is a potential web directory (/home/* or /var/www/*)
    if (!preg_match("/(\/home\d\/|\/var\/www\/)/i", $deploy_path)) {
        throw new Exception('ERROR: $deploy_path provided doesn\'t look like a web directory path?');
    }

    $current_release_dir = $current_path;
    $releases_dir = $deploy_path . '/releases';
    $new_release_dir = $releases_dir . '/' . $build . '_' . $commit;

    $remote_dir = $user . '@' . $host . ':' . $new_release_dir;

    // Command or path to invoke PHP
    $php = empty($php) ? 'php' : $php;

@endsetup

@story('deploy')
    verify_server_directories
    rsync
    manifest_file
    setup_symlinks
    verify_install
    generate_key
    optimise
    migrate
    activate_release 
    cleanup
@endstory



@task('debug', ['on' => 'localhost'])
    ls -la {{ $local_dir }}
@endtask


@task('debug_server', ['on' => 'web'])
    ls -la
@endtask


@task('verify_server_directories', ['on' => 'web'])
    echo "### Verifying server directories ###"

    cd {{ $deploy_path }}

    if [ ! -d "releases" ]; then
        mkdir releases
    fi

    if [ ! -d "storage/app/public" ]; then
        mkdir -p storage/app/public
    fi

    if [ ! -d "storage/framework/cache" ]; then
        mkdir -p storage/framework/cache/data
    fi

    if [ ! -d "storage/framework/sessions" ]; then
        mkdir -p storage/framework/sessions
    fi

    if [ ! -d "storage/framework/testing" ]; then
        mkdir -p storage/framework/testing
    fi

    if [ ! -d "storage/framework/views" ]; then
        mkdir -p storage/framework/views
    fi

    if [ ! -d "storage/logs" ]; then
        mkdir -p storage/logs
    fi

    cd {{ $current_release_dir }}

    if [ ! -e ".env" ]; then
        touch .env
    fi

@endtask



<!-- Sync repo files to remote dir server -->
<!-- https://explainshell.com/explain?cmd=rsync+-zrSlh+--stats+--exclude-from%3Ddeployment-exclude-list+%7B%7B+%24dir+%7D%7D%2F+%7B%7B+%24remote_dir+%7D%7D -->
@task('rsync', ['on' => 'localhost'])
    echo "### Deploying code from {{ $local_dir }} to {{ $remote_dir }} ###"
    rsync -zrSlh --stats --exclude-from=deploy-exclude-list {{ $local_dir }}/ {{ $remote_dir }}

    echo "### Sync .env {{ $local_dir }}/.env to {{ $remote_dir }} ###"
    rsync -zSh {{ $local_dir }}/.env {{ $remote_dir }}/.env
@endtask


<!-- Writing manifest file -->
@task('manifest_file', ['on' => 'web'])
    echo "### Writing deploy manifest file ###"
    echo -e "{\"build\":\""{{ $build }}"\", \"commit\":\""{{ $commit }}"\", \"branch\":\""{{ $branch }}"\"}" > {{ $new_release_dir }}/deploy-manifest.json
@endtask


<!-- Creating symbolic links -->
@task('setup_symlinks', ['on' => 'web'])
    echo "### Linking .env file to new release dir ({{ $deploy_path }}/.env -> {{ $new_release_dir }}/.env) ###"
    ln -nfs {{ $new_release_dir }}/.env {{ $current_release_dir }}/.env

    if [ -f {{ $new_release_dir }}/storage ]; then
        echo "### Moving existing storage dir ###"
        mv {{ $new_release_dir }}/storage {{ $new_release_dir }}/storage.orig 2>/dev/null
    fi

    echo "### Linking storage directory to new release dir ({{ $deploy_path }}/storage -> {{ $new_release_dir }}/storage) ###"
    ln -nfs {{ $deploy_path }}/storage {{ $new_release_dir }}/storage
@endtask


<!-- Checking Laravel -->
@task('verify_install', ['on' => 'web'])
    echo "### Verifying install ({{ $new_release_dir }}) ###"
    cd {{ $new_release_dir }}
    {{ $php }} artisan --version
@endtask


<!-- Generating Laravel New Key -->
@task('generate_key', ['on' => 'web'])
    cd {{ $new_release_dir }}
    {{ $php }} artisan key:generate
@endtask


<!-- Running Migrations -->
@task('migrate', ['on' => 'web'])
    echo "### Running migrations ###"
    cd {{ $new_release_dir }}
    {{ $php }} artisan migrate --force
@endtask


<!-- Running Migrations -->
@task('optimise', ['on' => 'web'])
    echo "### Clearing cache and optimising ###"
    cd {{ $new_release_dir }}

    {{ $php }} artisan cache:clear
    {{ $php }} artisan config:clear
    {{ $php }} artisan route:clear
    {{ $php }} artisan view:clear
    {{ $php }} artisan config:cache
@endtask


<!-- Activating the release (Live) -->
@task('activate_release', ['on' => 'web'])
    echo "### Activating new release ({{ $new_release_dir }} -> {{ $current_release_dir }}) ###"
    ln -nfs {{ $new_release_dir }}/* {{ $current_release_dir }}/
@endtask


<!-- Removing old releases -->
@task('cleanup', ['on' => 'web'])
    echo "### Executing cleanup command in {{ $releases_dir }} ###"
    ls -dt {{ $releases_dir }}/* | tail -n +3 | xargs rm -rf
    echo "### Deploy Finished Successfully!"
@endtask

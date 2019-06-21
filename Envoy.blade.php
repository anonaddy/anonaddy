@setup
require __DIR__."/vendor/autoload.php";
$dotenv = Dotenv\Dotenv::create(__DIR__, ".env");
$dotenv->load();

$userAndServer = getenv("ENVOY_USER_AND_SERVER");
$repository = "anonaddy/anonaddy";
$baseDir = getenv("ENVOY_BASE_DIR");
$releasesDir = "{$baseDir}/releases";
$persistentDir = "{$baseDir}/persistent";
$currentDir = "{$baseDir}/current";
$newReleaseName = date("Ymd-His");
$newReleaseDir = "{$releasesDir}/{$newReleaseName}";

function logMessage($message) {
  return "echo '\033[32m" .$message. "\033[0m';\n";
}
@endsetup

@servers(["local" => "127.0.0.1", "remote" => $userAndServer])

@story("deploy")
startDeployment
runTests
cloneRepository
runComposer
runNpm
generateAssets
updateSymlinks
optimizeInstallation
migrateDatabase
blessNewRelease
cleanOldReleases
finishDeploy
@endstory

@story("deploy-code")
runTests
deployOnlyCode
@endstory

@story("deploy-rollback")
deploymentRollback
@endstory

@task("startDeployment", ["on" => "local"])
{{ logMessage("🏃  Starting deployment...") }}
@endtask

@task("runTests", ["on" => "local"])
{{ logMessage("💻  Running Unit Tests...") }}
env -i bash -c "{{ getenv('ENVOY_RUN_TESTS') }}" || exit 1
@endtask

@task("cloneRepository", ["on" => "remote"])
{{ logMessage("🌀  Cloning repository...") }}
[ -d {{ $releasesDir }} ] || mkdir {{ $releasesDir }}
[ -d {{ $persistentDir }} ] || mkdir {{ $persistentDir }}
[ -d {{ $persistentDir }}/storage ] || mkdir {{ $persistentDir }}/storage
cd {{ $releasesDir }}

# Create the release dir
mkdir {{ $newReleaseDir }}

# Clone the repo
git clone --depth 1 git@github.com:{{ $repository }} {{ $newReleaseName }}

# Configure sparse checkout
cd {{ $newReleaseDir }}
git config core.sparsecheckout true
echo "*" > .git/info/sparse-checkout
echo "!storage" >> .git/info/sparse-checkout
echo "!public/build" >> .git/info/sparse-checkout
git read-tree -mu HEAD

# Mark release
cd {{ $newReleaseDir }}
echo "{{ $newReleaseName }}" > public/release-name.txt
@endtask

@task("runComposer", ["on" => "remote"])
{{ logMessage("🚚  Running Composer...") }}
cd {{ $newReleaseDir }}
composer install --prefer-dist --no-scripts --no-dev -q -o
@endtask

@task("runNpm", ["on" => "remote"])
{{ logMessage("📦  Running Npm...") }}
cd {{ $newReleaseDir }}
npm install --no-progress &> /dev/null
@endtask

@task("generateAssets", ["on" => "remote"])
{{ logMessage("🌅  Generating assets...") }}
cd {{ $newReleaseDir }}
npm run production --no-progress &> /dev/null
@endtask

@task("updateSymlinks", ["on" => "remote"])
{{ logMessage("🔗  Updating symlinks to persistent data...") }}
# Remove the storage directory and replace with persistent data
rm -rf {{ $newReleaseDir }}/storage
cd {{ $newReleaseDir }}
ln -nfs {{ $baseDir }}/persistent/storage storage

# Import the environment config
cd {{ $newReleaseDir }}
ln -nfs {{ $baseDir }}/.env .env
@endtask

@task("optimizeInstallation", ["on" => "remote"])
{{ logMessage("✨  Optimizing installation...") }}
cd {{ $newReleaseDir }}
php artisan clear-compiled
@endtask

@task("migrateDatabase", ["on" => "remote"])
{{ logMessage("🙈  Migrating database...") }}
cd {{ $newReleaseDir }}
php artisan migrate --force
@endtask

@task("blessNewRelease", ["on" => "remote"])
{{ logMessage("🙏  Blessing new release...") }}
ln -nfs {{ $newReleaseDir }} {{ $currentDir }}
cd {{ $newReleaseDir }}

php artisan storage:link

php artisan config:clear
php artisan view:clear
php artisan cache:clear

php artisan config:cache
php artisan view:cache
php artisan route:cache

php artisan queue:restart
@endtask

@task("cleanOldReleases", ["on" => "remote"])
{{ logMessage("🚾  Cleaning up old releases...") }}
# Delete all but the 5 most recent.
cd {{ $releasesDir }}
ls -dt {{ $releasesDir }}/* | tail -n +6 | xargs -d "\n" chown -R deployer .
ls -dt {{ $releasesDir }}/* | tail -n +6 | xargs -d "\n" rm -rf
@endtask

@task("finishDeploy", ["on" => "local"])
{{ logMessage("🚀  Application deployed!") }}
@endtask

@task("deployOnlyCode",["on" => "remote"])
{{ logMessage("💻  Deploying code changes...") }}
cd {{ $currentDir }}
git pull origin master

php artisan storage:link

php artisan config:clear
php artisan view:clear
php artisan cache:clear

php artisan config:cache
php artisan view:cache
php artisan route:cache

php artisan queue:restart
@endtask

@task("deploymentRollback")
cd {{ $releasesDir }}
ln -nfs {{ $releasesDir }}/$(find . -maxdepth 1 -name "20*" | sort  | tail -n 2 | head -n1) {{ $baseDir }}/current
echo "Rolled back to $(find . -maxdepth 1 -name "20*" | sort  | tail -n 2 | head -n1)"
@endtask

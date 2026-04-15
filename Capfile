# Load DSL and set up stages
require 'capistrano/setup'

# Include default deployment tasks
require 'capistrano/deploy'

# Load custom tasks from `lib/capistrano/tasks` if you have any defined there
Dir.glob('lib/capistrano/tasks/*.rake').each { |r| import r }

# Kör composer install efter deploy
namespace :composer do
  task :install do
    on roles(:all) do
      within release_path do
        execute '/usr/local/bin/composer', 'install', '--no-dev', '--optimize-autoloader', '--no-interaction'
      end
    end
  end
end

after 'deploy:updated', 'composer:install'

# Load DSL and set up stages
require 'capistrano/setup'

# Include default deployment tasks
require 'capistrano/deploy'

# Load Composer support
require 'capistrano/composer'

install_plugin Capistrano::Composer

# Load custom tasks from `lib/capistrano/tasks` if you have any defined there
Dir.glob('lib/capistrano/tasks/*.rake').each { |r| import r }

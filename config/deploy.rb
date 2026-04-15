# config/deploy.rb

lock '~> 3.18'

set :application, 'fkaros-web'
set :repo_url, 'git@github.com:tobiwanse/fkaros-web.git' # Byt ut mot ditt repo

#set :deploy_to, '/Users/admin/fkaros-web-test'

# Behåll 5 senaste releases (rollback: cap production deploy:rollback)
set :keep_releases, 5

# Delade filer – skapas en gång i shared/ och symlinkas vid varje deploy
set :linked_files, %w[.env]

# Delade mappar – bevaras mellan deploys
set :linked_dirs, %w[web/app/uploads]



# SSH agent forwarding – använder din lokala SSH-nyckel för GitHub-åtkomst på servern
set :ssh_options, { forward_agent: true }

# Loggnivå (:debug, :info, :warn, :error, :fatal)
set :log_level, :info

# Tidszonsformat för release-mappar
set :format_options, command_output: false

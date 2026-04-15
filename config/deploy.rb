# config/deploy.rb

lock '~> 3.18'

set :application, 'fkaros-web'
set :repo_url, 'https://github.com/tobiwanse/fkaros-web.git' # HTTPS undviker SSH-nyckelproblem på servern

# deploy_to sätts per miljö i config/deploy/production.rb, staging.rb etc.

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

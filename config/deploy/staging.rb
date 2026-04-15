# config/deploy/staging.rb

server '172.19.2.132', user: 'admin', roles: %w[app web db], ssh_options: { forward_agent: true }

set :branch, 'main'
set :stage, :staging

set :deploy_to, '/Users/admin/www/fkaros-web-test'
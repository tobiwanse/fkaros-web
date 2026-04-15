# config/deploy/production.rb

server '172.19.2.132', user: 'admin', roles: %w[app web db]

set :branch, 'main'
set :stage, :production
set :deploy_to, '/Users/admin/www/fkaros-web'

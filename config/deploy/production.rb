# config/deploy/production.rb

server 'DIN_SERVER_IP', user: 'deploy', roles: %w[app web db]

set :branch, 'main'
set :stage, :production

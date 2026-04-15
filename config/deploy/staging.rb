# config/deploy/staging.rb

server '172.19.2.132', user: 'admin', roles: %w[app web db]

set :branch, 'develop'
set :stage, :staging

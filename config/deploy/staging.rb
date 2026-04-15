# config/deploy/staging.rb

server 'localhost', user: 'deploy', roles: %w[app web db]

set :branch, 'develop'
set :stage, :staging

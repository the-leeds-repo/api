#!/usr/bin/env bash

# Requires the following environment variables:
# $TRAVIS_BRANCH = The name of the git branch that the build is running on.
# REPO_URI = The URI of the ECR repo to push to.
# CLUSTER = The name of the ECS cluster to deploy to.

# Bail out on first error.
set -e

# Get the environment from the branch.
case ${TRAVIS_BRANCH} in
    master )
        ENVIRONMENT=production
        ;;
    develop )
        ENVIRONMENT=staging
        ;;
esac

# Declare the configuration variables for the deployment.
echo "Setting deployment configuration for ${DEPLOYMENT}..."
export ENV_SECRET_ID=".env.api.${ENVIRONMENT}"
export PUBLIC_KEY_SECRET_ID="oauth-public.key.${ENVIRONMENT}"
export PRIVATE_KEY_SECRET_ID="oauth-private.key.${ENVIRONMENT}"

# Build the image.
./docker/build.sh

# Deploy the update to the services.
SERVICE="api" ./docker/deploy.sh
SERVICE="scheduler" ./docker/deploy.sh
SERVICE="queue-worker" ./docker/deploy.sh

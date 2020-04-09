#!/usr/bin/env bash

# Requires the following environment variables:
# $GCLOUD_SERVICE_KEY = The base64 encoded JSON file contents of the GCP service account.
# $GOOGLE_PROJECT_ID = The ID of the GCP project.
# $GOOGLE_COMPUTE_ZONE = The default GCP compute zone to use.
# $REPO_URI = The URI of the ECR repo to push to.
# $CLUSTER = The name of the ECS cluster to deploy to.

# Bail out on first error.
set -e

# Declare the configuration variables for the deployment.
echo "Setting deployment configuration..."
export ENV_SECRET_ID="env-api"
export PUBLIC_KEY_SECRET_ID="oauth-public-key"
export PRIVATE_KEY_SECRET_ID="oauth-private-key"

# Login to GCP.
echo $GCLOUD_SERVICE_KEY | base64 --decode | gcloud auth activate-service-account --key-file=-
gcloud --quiet config set project ${GOOGLE_PROJECT_ID}
gcloud --quiet config set compute/zone ${GOOGLE_COMPUTE_ZONE}

# Build the image.
./docker/build.sh

# Deploy the update to the services.
SERVICE="api" ./docker/deploy.sh
SERVICE="scheduler" ./docker/deploy.sh
SERVICE="queue-worker" ./docker/deploy.sh

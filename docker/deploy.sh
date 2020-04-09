#!/usr/bin/env bash

# Requires the following environment variables:
# $REPO_URI = The URI of the Docker repo to push to.
# $CLUSTER = The name of the ECS cluster.
# $SERVICE = The name of the ECS service.

# Bail out on first error.
set -e

# Configure Docker for GDP.
echo "Configuring Docker for GCP..."
gcloud auth configure-docker --quiet

# Push the Docker image to GCP.
echo "Pushing images to GCP..."
docker push ${REPO_URI}:latest
# docker push ${REPO_URI}:${TRAVIS_COMMIT}

# Update the service.
echo "Updating the GCP Docker service..."
#aws ecs update-service \
#    --cluster ${CLUSTER} \
#    --service ${SERVICE} \
#    --force-new-deployment

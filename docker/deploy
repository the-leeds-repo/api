#!/usr/bin/env bash

# Requires the following environment variables:
# $REPO_URI = The URI of the Docker repo to push to.
# $CLUSTER = The name of the ECS cluster.
# $SERVICE = The name of the ECS service.

# Bail out on first error.
set -e

# Login to the ECR.
echo "Logging in to ECR..."
$(aws ecr get-login --no-include-email)

# Push the Docker image to ECR.
echo "Pushing images to ECR..."
docker push ${REPO_URI}:latest
# docker push ${REPO_URI}:${TRAVIS_COMMIT}

# Update the service.
echo "Updating the ECS service..."
aws ecs update-service \
    --cluster ${CLUSTER} \
    --service ${SERVICE} \
    --force-new-deployment

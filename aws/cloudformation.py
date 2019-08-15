# ==================================================
# This stack creates the API infrastructure.
# ==================================================
from troposphere import Template, Parameter, Ref, GetAtt, Join, Base64, Output, Sub
import troposphere.ec2 as ec2
import troposphere.rds as rds
import troposphere.elasticache as elasticache
import troposphere.sqs as sqs
import troposphere.s3 as s3
import troposphere.iam as iam
import troposphere.ecs as ecs
import troposphere.ecr as ecr
import troposphere.logs as logs
import troposphere.elasticloadbalancingv2 as elb
import troposphere.autoscaling as autoscaling
import troposphere.elasticsearch as elasticsearch
import uuid

# ==================================================
# Template details.
# ==================================================
template = Template('Create the infrastructure needed to run The Leeds Repo API')
template.add_version('2010-09-09')

# ==================================================
# Parameters.
# ==================================================
uuid_parameter = template.add_parameter(
  Parameter(
    'Uuid',
    Type='String',
    Default=str(uuid.uuid4()),
    Description='The unique ID for this stack.',
    MinLength='36',
    MaxLength='36'
  )
)

environment_parameter = template.add_parameter(
  Parameter(
    'Environment',
    Type='String',
    Description='The environment this stack is for (e.g. production or staging).',
    MinLength='1'
  )
)

certificate_arn_parameter = template.add_parameter(
  Parameter(
    'CertificateArn',
    Type='String',
    Description='The ARN for the API load balancer SSL certificate.'
  )
)

vpc_parameter = template.add_parameter(
  Parameter(
    'Vpc',
    Type='AWS::EC2::VPC::Id',
    Description='The Virtual Private Cloud (VPC) to launch the stack in.'
  )
)

subnets_parameter = template.add_parameter(
  Parameter(
    'Subnets',
    Type='List<AWS::EC2::Subnet::Id>',
    Description='The list of subnet IDs, for at least two Availability Zones in the region in your Virtual Private Cloud (VPC).'
  )
)

database_password_parameter = template.add_parameter(
  Parameter(
    'DatabasePassword',
    Description='The database admin password.',
    NoEcho=True,
    Type='String',
    MinLength='8',
    MaxLength='41',
    AllowedPattern='[a-zA-Z0-9]*',
    ConstraintDescription='Must only contain alphanumeric characters.'
  )
)

database_class_parameter = template.add_parameter(
  Parameter(
    'DatabaseClass',
    Description='The database instance class.',
    Type='String',
    Default='db.t3.micro',
    AllowedValues=[
      'db.t3.micro',
      'db.t3.small',
      'db.t3.medium',
      'db.t3.large',
      'db.t3.xlarge',
      'db.t3.2xlarge'
    ],
    ConstraintDescription='Must select a valid database instance type.'
  )
)

database_allocated_storage_parameter = template.add_parameter(
  Parameter(
    'DatabaseAllocatedStorage',
    Description='The size of the database (GiB).',
    Default='10',
    Type='Number',
    MinValue='5',
    MaxValue='1024',
    ConstraintDescription='Must be between 5 and 1024 GiB.'
  )
)

redis_node_class_parameter = template.add_parameter(
  Parameter(
    'RedisNodeClass',
    Description='The Redis node class.',
    Type='String',
    Default='cache.t2.micro',
    AllowedValues=[
      'cache.t2.micro',
      'cache.t2.small',
      'cache.t2.medium'
    ],
    ConstraintDescription='Must select a valid Redis node type.'
  )
)

redis_nodes_count_parameter = template.add_parameter(
  Parameter(
    'RedisNodesCount',
    Description='The number of Redis nodes to have in the cluster.',
    Default='1',
    Type='Number',
    MinValue='1',
    ConstraintDescription='Must be 1 or more.'
  )
)

api_instance_class_parameter = template.add_parameter(
  Parameter(
    'ApiInstanceClass',
    Description='The API EC2 instance class.',
    Type='String',
    Default='t3.micro',
    AllowedValues=[
      't3.nano',
      't3.micro',
      't3.small',
      't3.medium',
      't3.large',
      't3.xlarge',
      't3.2xlarge'
    ],
    ConstraintDescription='Must select a valid API instance type.'
  )
)

api_instance_count_parameter = template.add_parameter(
  Parameter(
    'ApiInstanceCount',
    Description='The number of API EC2 instances to load balance between.',
    Type='Number',
    Default='2',
    MinValue='1',
    ConstraintDescription='Must be 1 or more.'
  )
)

api_task_count_parameter = template.add_parameter(
  Parameter(
    'ApiTaskCount',
    Description='The number of API containers to run.',
    Type='Number',
    Default='0',
    MinValue='0',
    ConstraintDescription='Must be 0 or more.'
  )
)

scheduler_task_count_parameter = template.add_parameter(
  Parameter(
    'SchedulerTaskCount',
    Description='The number of scheduler containers to run.',
    Type='Number',
    Default='0',
    MinValue='0',
    MaxValue='1',
    ConstraintDescription='Must be either 0 or 1.'
  )
)

queue_worker_task_count_parameter = template.add_parameter(
  Parameter(
    'QueueWorkerTaskCount',
    Description='The number of queue worker containers to run.',
    Type='Number',
    Default='0',
    MinValue='0',
    ConstraintDescription='Must be 0 or more.'
  )
)

elasticsearch_instance_class_parameter = template.add_parameter(
  Parameter(
    'ElasticsearchInstanceClass',
    Description='The Elasticseach instance class.',
    Type='String',
    Default='t2.small.elasticsearch',
    AllowedValues=[
      't2.micro.elasticsearch',
      't2.small.elasticsearch',
      't2.medium.elasticsearch'
    ],
    ConstraintDescription='Must select a valid Elasticsearch instance type.'
  )
)

elasticsearch_instance_count_parameter = template.add_parameter(
  Parameter(
    'ElasticsearchInstanceCount',
    Description='The number of Elasticsearch nodes to run.',
    Type='Number',
    Default='1',
    MinValue='1',
    ConstraintDescription='Must be 1 or more.'
  )
)

# ==================================================
# Variables.
# ==================================================
default_queue_name_variable = Join('-', ['default', Ref(environment_parameter), Ref(uuid_parameter)])
notifications_queue_name_variable = Join('-', ['notifications', Ref(environment_parameter), Ref(uuid_parameter)])
uploads_bucket_name_variable = Join('-', ['uploads', Ref(environment_parameter), Ref(uuid_parameter)])
api_launch_template_name_variable = Join('-', ['api-launch-template', Ref(environment_parameter), Ref(uuid_parameter)])
docker_repository_name_variable = Join('-', ['api', Ref(environment_parameter), Ref(uuid_parameter)])
api_log_group_name_variable = Join('-', ['api', Ref(environment_parameter), Ref(uuid_parameter)])
queue_worker_log_group_name_variable = Join('-', ['queue-worker', Ref(environment_parameter), Ref(uuid_parameter)])
scheduler_log_group_name_variable = Join('-', ['scheduler', Ref(environment_parameter), Ref(uuid_parameter)])
api_task_definition_family_variable = Join('-', ['api', Ref(environment_parameter), Ref(uuid_parameter)])
queue_worker_task_definition_family_variable = Join('-', ['queue-worker', Ref(environment_parameter), Ref(uuid_parameter)])
scheduler_task_definition_family_variable = Join('-', ['scheduler', Ref(environment_parameter), Ref(uuid_parameter)])
api_user_name_variable = Join('-', ['api', Ref(environment_parameter), Ref(uuid_parameter)])
ci_user_name_variable = Join('-', ['ci', Ref(environment_parameter), Ref(uuid_parameter)])
database_name_variable = 'the_leeds_repo'
database_username_variable = 'the_leeds_repo'
elasticsearch_domain_name_variable=Join('-', ['search', Ref(environment_parameter), Ref(uuid_parameter)])

# ==================================================
# Resources.
# ==================================================
load_balancer_security_group_resource = template.add_resource(
  ec2.SecurityGroup(
    'LoadBalancerSecurityGroup',
    GroupDescription='For connecting to the API load balancer',
    SecurityGroupIngress=[
      ec2.SecurityGroupRule(
        Description='HTTP access from the public',
        IpProtocol='tcp',
        FromPort='80',
        ToPort='80',
        CidrIp='0.0.0.0/0'
      ),
      ec2.SecurityGroupRule(
        Description='HTTPS access from the public',
        IpProtocol='tcp',
        FromPort='443',
        ToPort='443',
        CidrIp='0.0.0.0/0'
      )
    ]
  )
)

api_security_group_resource = template.add_resource(
  ec2.SecurityGroup(
    'ApiSecurityGroup',
    GroupDescription='For connecting to the API containers',
    SecurityGroupIngress=[
      ec2.SecurityGroupRule(
        Description='Full access from the load balancer',
        IpProtocol='tcp',
        FromPort='0',
        ToPort='65535',
        SourceSecurityGroupName=Ref(load_balancer_security_group_resource)
      )
    ]
  )
)

database_security_group_resource = template.add_resource(
  ec2.SecurityGroup(
    'DatabaseSecurityGroup',
    GroupDescription='For connecting to the MySQL instance',
    SecurityGroupIngress=[
      ec2.SecurityGroupRule(
        Description='MySQL access from the API containers',
        IpProtocol='tcp',
        FromPort='3306',
        ToPort='3306',
        SourceSecurityGroupName=Ref(api_security_group_resource)
      )
    ]
  )
)

redis_security_group_resource = template.add_resource(
  ec2.SecurityGroup(
    'RedisSecurityGroup',
    GroupDescription='For connecting to the Redis cluster',
    SecurityGroupIngress=[
      ec2.SecurityGroupRule(
        Description='Redis access from the API containers',
        IpProtocol='tcp',
        FromPort='6379',
        ToPort='6379',
        SourceSecurityGroupName=Ref(api_security_group_resource)
      )
    ]
  )
)

database_subnet_group_resource = template.add_resource(
  rds.DBSubnetGroup(
    'DatabaseSubnetGroup',
    DBSubnetGroupDescription='Subnets available for the RDS instance',
    SubnetIds=Ref(subnets_parameter)
  )
)

database_resource = template.add_resource(
  rds.DBInstance(
    'Database',
    DBName=database_name_variable,
    AllocatedStorage=Ref(database_allocated_storage_parameter),
    DBInstanceClass=Ref(database_class_parameter),
    Engine='MySQL',
    EngineVersion='5.7',
    MasterUsername=database_username_variable,
    MasterUserPassword=Ref(database_password_parameter),
    VPCSecurityGroups=[GetAtt(database_security_group_resource, 'GroupId')],
    DBSubnetGroupName=Ref(database_subnet_group_resource),
    PubliclyAccessible=False
  )
)

redis_subnet_group_resource = template.add_resource(
  elasticache.SubnetGroup(
    'RedisSubnetGroup',
    Description='Subnets available for the Redis cluster',
    SubnetIds=Ref(subnets_parameter)
  )
)

redis_resource = template.add_resource(
  elasticache.CacheCluster(
    'Redis',
    Engine='redis',
    EngineVersion='4.0',
    CacheNodeType=Ref(redis_node_class_parameter),
    NumCacheNodes=Ref(redis_nodes_count_parameter),
    VpcSecurityGroupIds=[GetAtt(redis_security_group_resource, 'GroupId')],
    CacheSubnetGroupName=Ref(redis_subnet_group_resource)
  )
)

default_queue_resource = template.add_resource(
  sqs.Queue(
    'DefaultQueue',
    QueueName=default_queue_name_variable
  )
)

notifications_queue_resource = template.add_resource(
  sqs.Queue(
    'NotificationsQueue',
    QueueName=notifications_queue_name_variable
  )
)

uploads_bucket_resource = template.add_resource(
  s3.Bucket(
    'UploadsBucket',
    BucketName=uploads_bucket_name_variable,
    AccessControl='Private'
  )
)

ecs_cluster_role_resource = template.add_resource(
  iam.Role(
    'ECSClusterRole',
    ManagedPolicyArns=['arn:aws:iam::aws:policy/service-role/AmazonEC2ContainerServiceforEC2Role'],
    AssumeRolePolicyDocument={
      'Version': '2012-10-17',
      'Statement': [
        {
          'Action': 'sts:AssumeRole',
          'Principal': {
            'Service': 'ec2.amazonaws.com'
          },
          'Effect': 'Allow'
        }
      ]
    }
  )
)

ec2_instance_profile_resource = template.add_resource(
  iam.InstanceProfile(
    'EC2InstanceProfile',
    Roles=[Ref(ecs_cluster_role_resource)]
  )
)

ecs_cluster_resource = template.add_resource(
  ecs.Cluster(
    'ApiCluster'
  )
)

launch_template_resource = template.add_resource(
  ec2.LaunchTemplate(
    'LaunchTemplate',
    LaunchTemplateName=api_launch_template_name_variable,
    LaunchTemplateData=ec2.LaunchTemplateData(
      ImageId='ami-066826c6a40879d75',
      InstanceType=Ref(api_instance_class_parameter),
      IamInstanceProfile=ec2.IamInstanceProfile(
        Arn=GetAtt(ec2_instance_profile_resource, 'Arn')
      ),
      InstanceInitiatedShutdownBehavior='terminate',
      Monitoring=ec2.Monitoring(Enabled=True),
      SecurityGroups=[Ref(api_security_group_resource)],
      BlockDeviceMappings=[
        ec2.BlockDeviceMapping(
          DeviceName='/dev/xvdcz',
          Ebs=ec2.EBSBlockDevice(
            DeleteOnTermination=True,
            VolumeSize=22,
            VolumeType='gp2'
          )
        )
      ],
      UserData=Base64(
        Join('', [
          '#!/bin/bash\n',
          'echo ECS_CLUSTER=',
          Ref(ecs_cluster_resource),
          ' >> /etc/ecs/ecs.config;echo ECS_BACKEND_HOST= >> /etc/ecs/ecs.config;'
        ])
      )
    )
  )
)

docker_repository_resource = template.add_resource(
  ecr.Repository(
    'DockerRepository',
    RepositoryName=docker_repository_name_variable,
    LifecyclePolicy=ecr.LifecyclePolicy(
      LifecyclePolicyText='{"rules":[{"rulePriority":1,"description":"Remove untagged images older than 1 week","selection":{"tagStatus":"untagged","countType":"sinceImagePushed","countUnit":"days","countNumber":7},"action":{"type":"expire"}}]}'
    )
  )
)

api_log_group_resource = template.add_resource(
  logs.LogGroup(
    'ApiLogGroup',
    LogGroupName=api_log_group_name_variable,
    RetentionInDays=7
  )
)

queue_worker_log_group_resource = template.add_resource(
  logs.LogGroup(
    'QueueWorkerLogGroup',
    LogGroupName=queue_worker_log_group_name_variable,
    RetentionInDays=7
  )
)

scheduler_log_group_resource = template.add_resource(
  logs.LogGroup(
    'SchedulerLogGroup',
    LogGroupName=scheduler_log_group_name_variable,
    RetentionInDays=7
  )
)

api_task_definition_resource = template.add_resource(
  ecs.TaskDefinition(
    'ApiTaskDefinition',
    Family=api_task_definition_family_variable,
    NetworkMode='bridge',
    RequiresCompatibilities=['EC2'],
    ContainerDefinitions=[ecs.ContainerDefinition(
      Name='api',
      Image=Join('.', [
        Ref('AWS::AccountId'),
        'dkr.ecr',
        Ref('AWS::Region'),
        Join('/', [
          'amazonaws.com',
          Ref(docker_repository_resource)
        ])
      ]),
      MemoryReservation='256',
      PortMappings=[ecs.PortMapping(
        HostPort='0',
        ContainerPort='80',
        Protocol='tcp'
      )],
      Essential=True,
      LogConfiguration=ecs.LogConfiguration(
        LogDriver='awslogs',
        Options={
          'awslogs-group': Ref(api_log_group_resource),
          'awslogs-region': Ref('AWS::Region'),
          'awslogs-stream-prefix': 'ecs'
        }
      )
    )]
  )
)

queue_worker_task_definition_resource = template.add_resource(
  ecs.TaskDefinition(
    'QueueWorkerTaskDefinition',
    Family=queue_worker_task_definition_family_variable,
    NetworkMode='bridge',
    RequiresCompatibilities=['EC2'],
    ContainerDefinitions=[ecs.ContainerDefinition(
      Name='api',
      Image=Join('.', [
        Ref('AWS::AccountId'),
        'dkr.ecr',
        Ref('AWS::Region'),
        Join('/', [
            'amazonaws.com',
            Ref(docker_repository_resource)
        ])
      ]),
      MemoryReservation='256',
      Essential=True,
      LogConfiguration=ecs.LogConfiguration(
        LogDriver='awslogs',
        Options={
          'awslogs-group': Ref(queue_worker_log_group_resource),
          'awslogs-region': Ref('AWS::Region'),
          'awslogs-stream-prefix': 'ecs'
        }
      ),
      Command=[
        'php',
        'artisan',
        'queue:work',
        '--tries=1',
        Join('=', ['--queue', Join(',', [default_queue_name_variable, notifications_queue_name_variable])])
      ],
      WorkingDirectory='/var/www/html',
      HealthCheck=ecs.HealthCheck(
        Command=[
          'CMD-SHELL',
          'php -v || exit 1'
        ],
        Interval=30,
        Retries=3,
        Timeout=5
      )
    )]
  )
)

scheduler_task_definition_resource = template.add_resource(
  ecs.TaskDefinition(
    'SchedulerTaskDefinition',
    Family=scheduler_task_definition_family_variable,
    NetworkMode='bridge',
    RequiresCompatibilities=['EC2'],
    ContainerDefinitions=[ecs.ContainerDefinition(
      Name='api',
      Image=Join('.', [
        Ref('AWS::AccountId'),
        'dkr.ecr',
        Ref('AWS::Region'),
        Join('/', [
          'amazonaws.com',
          docker_repository_name_variable
        ])
      ]),
      MemoryReservation='256',
      Essential=True,
      LogConfiguration=ecs.LogConfiguration(
        LogDriver='awslogs',
        Options={
          'awslogs-group': Ref(scheduler_log_group_resource),
          'awslogs-region': Ref('AWS::Region'),
          'awslogs-stream-prefix': 'ecs'
        }
      ),
      Command=[
        'php',
        'artisan',
        'schedule:loop'
      ],
      WorkingDirectory='/var/www/html',
      HealthCheck=ecs.HealthCheck(
        Command=[
          'CMD-SHELL',
          'php -v || exit 1'
        ],
        Interval=30,
        Retries=3,
        Timeout=5
      )
    )]
  )
)

load_balancer_resource = template.add_resource(
  elb.LoadBalancer(
    'LoadBalancer',
    Scheme='internet-facing',
    SecurityGroups=[GetAtt(load_balancer_security_group_resource, 'GroupId')],
    Subnets=Ref(subnets_parameter),
  )
)

api_target_group_resource = template.add_resource(
  elb.TargetGroup(
    'ApiTargetGroup',
    HealthCheckIntervalSeconds=30,
    HealthCheckPath='/',
    HealthCheckPort='traffic-port',
    HealthCheckProtocol='HTTP',
    HealthCheckTimeoutSeconds=5,
    HealthyThresholdCount=5,
    UnhealthyThresholdCount=2,
    Port=80,
    Protocol='HTTP',
    TargetType='instance',
    VpcId=Ref(vpc_parameter),
    DependsOn=[load_balancer_resource]
  )
)

load_balancer_listener_resource = template.add_resource(
  elb.Listener(
    'LoadBalancerListener',
    LoadBalancerArn=Ref(load_balancer_resource),
    Port=443,
    Protocol='HTTPS',
    DefaultActions=[elb.Action(
      Type='forward',
      TargetGroupArn=Ref(api_target_group_resource)
    )],
    Certificates=[
      elb.Certificate(
        CertificateArn=Ref(certificate_arn_parameter)
      )
    ]
  )
)

ecs_service_role_resource = template.add_resource(
  iam.Role(
    'ECSServiceRole',
    AssumeRolePolicyDocument={
        'Version': '2012-10-17',
        'Statement': [
            {
                'Action': 'sts:AssumeRole',
                'Effect': 'Allow',
                'Principal': {
                    'Service': 'ecs.amazonaws.com'
                }
            }
        ]
    },
    Policies=[
      iam.Policy(
        PolicyName='ECSServiceRolePolicy',
        PolicyDocument={
          'Statement': [
            {
              'Effect': 'Allow',
              'Action': [
                'ec2:AttachNetworkInterface',
                'ec2:CreateNetworkInterface',
                'ec2:CreateNetworkInterfacePermission',
                'ec2:DeleteNetworkInterface',
                'ec2:DeleteNetworkInterfacePermission',
                'ec2:Describe*',
                'ec2:DetachNetworkInterface',
                'elasticloadbalancing:DeregisterInstancesFromLoadBalancer',
                'elasticloadbalancing:DeregisterTargets',
                'elasticloadbalancing:Describe*',
                'elasticloadbalancing:RegisterInstancesWithLoadBalancer',
                'elasticloadbalancing:RegisterTargets',
                'route53:ChangeResourceRecordSets',
                'route53:CreateHealthCheck',
                'route53:DeleteHealthCheck',
                'route53:Get*',
                'route53:List*',
                'route53:UpdateHealthCheck',
                'servicediscovery:DeregisterInstance',
                'servicediscovery:Get*',
                'servicediscovery:List*',
                'servicediscovery:RegisterInstance',
                'servicediscovery:UpdateInstanceCustomHealthStatus'
              ],
              'Resource': '*'
            },
            {
              'Effect': 'Allow',
              'Action': [
                'ec2:CreateTags'
              ],
              'Resource': 'arn:aws:ec2:*:*:network-interface/*'
            }
          ]
        }
      )
    ]
  )
)

api_service_resource = template.add_resource(
  ecs.Service(
    'ApiService',
    ServiceName='api',
    Cluster=Ref(ecs_cluster_resource),
    TaskDefinition=Ref(api_task_definition_resource),
    DeploymentConfiguration=ecs.DeploymentConfiguration(
      MinimumHealthyPercent=100,
      MaximumPercent=200
    ),
    DesiredCount=Ref(api_task_count_parameter),
    LaunchType='EC2',
    LoadBalancers=[ecs.LoadBalancer(
      ContainerName='api',
      ContainerPort=80,
      TargetGroupArn=Ref(api_target_group_resource)
    )],
    Role=Ref(ecs_service_role_resource),
    DependsOn=[load_balancer_listener_resource]
  )
)

queue_worker_service_resource = template.add_resource(
  ecs.Service(
    'QueueWorkerService',
    ServiceName='queue-worker',
    Cluster=Ref(ecs_cluster_resource),
    TaskDefinition=Ref(queue_worker_task_definition_resource),
    DeploymentConfiguration=ecs.DeploymentConfiguration(
      MinimumHealthyPercent=0,
      MaximumPercent=100
    ),
    DesiredCount=Ref(queue_worker_task_count_parameter),
    LaunchType='EC2'
  )
)

scheduler_service_resource = template.add_resource(
  ecs.Service(
    'SchedulerService',
    ServiceName='scheduler',
    Cluster=Ref(ecs_cluster_resource),
    TaskDefinition=Ref(scheduler_task_definition_resource),
    DeploymentConfiguration=ecs.DeploymentConfiguration(
      MinimumHealthyPercent=0,
      MaximumPercent=100
    ),
    DesiredCount=Ref(scheduler_task_count_parameter),
    LaunchType='EC2'
  )
)

autoscaling_group_resource = template.add_resource(
  autoscaling.AutoScalingGroup(
    'AutoScalingGroup',
    DesiredCapacity=Ref(api_instance_count_parameter),
    MinSize=Ref(api_instance_count_parameter),
    MaxSize=Ref(api_instance_count_parameter),
    LaunchTemplate=autoscaling.LaunchTemplateSpecification(
      LaunchTemplateId=Ref(launch_template_resource),
      Version=GetAtt(launch_template_resource, 'LatestVersionNumber')
    ),
    AvailabilityZones=['eu-west-1a', 'eu-west-1b', 'eu-west-1c']
  )
)

api_user_resource = template.add_resource(
  iam.User(
    'ApiUser',
    UserName=api_user_name_variable,
    Policies=[
      iam.Policy(
        PolicyName='ApiUserPolicy',
        PolicyDocument={
          'Version': '2012-10-17',
          'Statement': [
            {
              'Action': 's3:*',
              'Effect': 'Allow',
              'Resource': [
                GetAtt(uploads_bucket_resource, 'Arn'),
                Join('/', [GetAtt(uploads_bucket_resource, 'Arn'), '*'])
              ]
            },
            {
              'Action': 'sqs:*',
              'Effect': 'Allow',
              'Resource': GetAtt(default_queue_resource, 'Arn')
            },
            {
              'Action': 'sqs:*',
              'Effect': 'Allow',
              'Resource': GetAtt(notifications_queue_resource, 'Arn')
            }
          ]
        }
      )
    ]
  )
)

ci_user_resource = template.add_resource(
  iam.User(
    'CiUser',
    UserName=ci_user_name_variable,
    Policies=[
      iam.Policy(
        PolicyName='CiUserPolicy',
        PolicyDocument={
          'Version': '2012-10-17',
          'Statement': [
            {
              'Action': 'ecr:*',
              'Effect': 'Allow',
              'Resource': '*'
            },
            {
              'Action': 'ecs:UpdateService',
              'Effect': 'Allow',
              'Resource': '*'
            },
            {
              'Action': 'secretsmanager:GetSecretValue',
              'Effect': 'Allow',
              'Resource': '*'
            }
          ]
        }
      )
    ]
  )
)

elasticsearch_resource = template.add_resource(
  elasticsearch.Domain(
    'Elasticsearch',
    AccessPolicies={
      'Version': '2012-10-17',
      'Statement': [
        {
          'Effect': 'Allow',
          'Principal': {
            'AWS': GetAtt(api_user_resource, 'Arn')
          },
          'Action': 'es:*',
          'Resource': Sub('arn:aws:es:${AWS::Region}:${AWS::AccountId}:domain/${DomainName}/*', DomainName=elasticsearch_domain_name_variable)
        }
      ]
    },
    DomainName=elasticsearch_domain_name_variable,
    ElasticsearchClusterConfig=elasticsearch.ElasticsearchClusterConfig(
      InstanceCount=Ref(elasticsearch_instance_count_parameter),
      InstanceType=Ref(elasticsearch_instance_class_parameter)
    ),
    ElasticsearchVersion='6.3'
  )
)

# ==================================================
# Outputs.
# ==================================================
template.add_output(
  Output(
    'DatabaseName',
    Description='The database name',
    Value=database_username_variable
  )
)

template.add_output(
  Output(
    'DatabaseUsername',
    Description='The username for the database',
    Value=database_username_variable
  )
)

template.add_output(
  Output(
    'DatabaseHost',
    Description='The host of the RDS instance',
    Value=GetAtt(database_resource, 'Endpoint.Address')
  )
)

template.add_output(
  Output(
    'DatabasePort',
    Description='The port of the RDS instance',
    Value=GetAtt(database_resource, 'Endpoint.Port')
  )
)

template.add_output(
  Output(
    'RedisHost',
    Description='The host of the Redis instance',
    Value=GetAtt(redis_resource, 'RedisEndpoint.Address')
  )
)

template.add_output(
  Output(
    'RedisPort',
    Description='The port of the Redis instance',
    Value=GetAtt(redis_resource, 'RedisEndpoint.Port')
  )
)

template.add_output(
  Output(
    'DefaultQueue',
    Description='The name of the default queue',
    Value=default_queue_name_variable
  )
)

template.add_output(
  Output(
    'NotificationsQueue',
    Description='The name of the notifications queue',
    Value=notifications_queue_name_variable
  )
)

template.add_output(
  Output(
    'LoadBalancerDomain',
    Description='The domain name of the load balancer',
    Value=GetAtt(load_balancer_resource, 'DNSName')
  )
)

template.add_output(
  Output(
    'ElasticsearchHost',
    Description='The host of the Elasticsearch instance',
    Value=GetAtt(elasticsearch_resource, 'DomainEndpoint')
  )
)

# ==================================================
# Print the generated template in JSON.
# ==================================================
print(template.to_json())

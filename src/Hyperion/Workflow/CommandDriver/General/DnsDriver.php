<?php
namespace Hyperion\Workflow\CommandDriver\General;

use Aws\Common\Credentials\Credentials;
use Aws\Route53\Route53Client;
use Hyperion\Dbal\Entity\Distribution;
use Hyperion\Dbal\Enum\Entity;
use Hyperion\Workflow\CommandDriver\AbstractCommandDriver;
use Hyperion\Workflow\CommandDriver\CommandDriverInterface;
use Hyperion\Workflow\CommandDriver\Traits\InstanceReportTrait;
use Hyperion\Workflow\Enum\ChangeType;
use Hyperion\Workflow\Exception\CommandFailedException;

/**
 * Bind a DNS record
 *
 * This is an experimental feature - it is limited to Route53 and uses your environment credentials to connect to AWS
 *
 * @experimental
 */
class DnsDriver extends AbstractCommandDriver implements CommandDriverInterface
{
    use InstanceReportTrait;

    public function execute()
    {
        $this->initAction();
        if ($distro_id = $this->action->getDistribution()) {
            /** @var Distribution $distribution */
            $distribution = $this->dbal->retrieve(Entity::DISTRIBUTION(), $distro_id);
        } else {
            throw new CommandFailedException("A distribution is required");
        }

        $action = $this->getConfig('action', ChangeType::UPDATE);
        $zone   = $this->getConfig('zone');
        $name   = $this->injectName($this->getConfig('name', ''), $distribution);
        $value  = $this->getConfig('value');

        $distribution->setDns($name);
        $this->dbal->update($distribution);

        if (!$zone) {
            throw new CommandFailedException("Zone is mandatory");
        }

        $record_set = [
            'Name' => $name,
            'Type' => $this->getConfig('type', 'A'),
        ];

        if ($action == ChangeType::UPDATE) {
            if (!$value) {
                throw new CommandFailedException("Value is mandatory for UPDATE requests");
            }
            $action                        = 'UPSERT';
            $record_set['TTL']             = $this->getConfig('ttl', 60);
            $record_set['ResourceRecords'] = [
                ['Value' => $value],
            ];
        }

        $credentials = new Credentials(
            $this->service->getCredentials()->getIdentity(),
            $this->service->getCredentials()->getSecret()
        );

        $r53 = Route53Client::factory(['credentials' => $credentials]);
        $r53->changeResourceRecordSets(
            [
                'HostedZoneId' => $zone,
                'ChangeBatch'  => [
                    'Changes' => [
                        [
                            'Action'            => $action,
                            'ResourceRecordSet' => $record_set,
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * Inject a DNS name with special variables and clean the result
     *
     * @param string       $value
     * @param Distribution $distribution
     * @return string
     */
    protected function injectName($value, Distribution $distribution)
    {
        $value = str_replace('$EVENT_ID', $this->action->getId(), $value);

        $value = str_replace('$PROJECT_ID', $this->project->getId(), $value);
        $value = str_replace('$PROJECT_NAME', $this->project->getName(), $value);

        $value = str_replace('$ENVIRONMENT_ID', $this->environment->getId(), $value);
        $value = str_replace('$ENVIRONMENT_NAME', $this->environment->getName(), $value);

        $value = str_replace('$BUILD_ID', $distribution->getId(), $value);
        $value = str_replace('$BUILD_NAME', $distribution->getName(), $value);

        $out = '';
        for ($i = 0; $i < strlen($value); $i++) {
            $c = strtolower($value{$i});
            if (ctype_alnum($c) || ($c == '.' || ($c == '*'))) {
                $out .= $c;
            } else {
                $out .= '-';
            }
        }

        return $out;
    }

}
 
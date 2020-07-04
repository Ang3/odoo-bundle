<?php

namespace Ang3\Bundle\OdooBundle\Validator\Constraints;

use Ang3\Bundle\OdooBundle\Connection\ClientRegistry;
use Ang3\Component\Odoo\Expression\CustomDomain;
use Ang3\Component\Odoo\Expression\DomainInterface;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class OdooRecordValidator extends ConstraintValidator
{
    /**
     * @var ClientRegistry
     */
    private $clientRegistry;

    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    /**
     * @var Security
     */
    private $security;

    public function __construct(ClientRegistry $clientRegistry, Security $security)
    {
        $this->clientRegistry = $clientRegistry;
        $this->expressionLanguage = new ExpressionLanguage();
        $this->security = $security;
    }

    /**
     * @param mixed $value
     *
     * @throws LogicException           when the connection was not found
     * @throws RuntimeException         when the domains expression is not valid
     * @throws InvalidArgumentException when the value of constraint domains is not valid
     */
    public function validate($value, Constraint $constraint): void
    {
        if (!($constraint instanceof OdooRecord)) {
            throw new UnexpectedTypeException($constraint, OdooRecord::class);
        }

        if (null === $value || '' === $value || false === $value) {
            return;
        }

        $client = $this->clientRegistry->get($constraint->connection ?: 'default');
        $value = is_scalar($value) ? (int) $value : $value;

        if (!is_int($value) || $value < 1) {
            $this->context
                ->buildViolation($constraint->typeErrorMessage)
                ->addViolation()
            ;
        }

        $expressionBuilder = $client->expr();

        if (is_string($constraint->domains) && $constraint->domains) {
            try {
                $domains = $this->expressionLanguage->evaluate(
                    $constraint->domains,
                    [
                        'expr' => $expressionBuilder,
                        'this' => $this->context->getObject(),
                        'user' => $this->security->getUser(),
                    ]
                );

                if (!($domains instanceof DomainInterface) && !is_array($domains)) {
                    throw new InvalidArgumentException(sprintf('The evaluation of domains expression must returns value of type %s|array<%s|array>, %s returned', DomainInterface::class, DomainInterface::class, gettype($domains)));
                }
            } catch (\Throwable $e) {
                throw new RuntimeException(sprintf('The domains expression "%s" is not valid', $constraint->domains), 0, $e);
            }

            $domains = $expressionBuilder->andX(
                $expressionBuilder->eq('id', $value),
                is_array($domains) ? new CustomDomain($domains) : $domains
            );
        } else {
            $domains = $expressionBuilder->eq('id', $value);
        }

        if (0 === $client->count($constraint->model, $domains)) {
            $this->context
                ->buildViolation($constraint->notFoundMessage)
                ->setParameter('{{ model_id }}', (string) $value)
                ->setParameter('{{ model_name }}', (string) $constraint->model)
                ->addViolation()
            ;
        }
    }
}

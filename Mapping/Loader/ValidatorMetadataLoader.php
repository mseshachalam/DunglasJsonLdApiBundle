<?php

/*
 * This file is part of the DunglasJsonLdApiBundle package.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dunglas\JsonLdApiBundle\Mapping\Loader;

use Dunglas\JsonLdApiBundle\Mapping\ClassMetadata;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Mapping\Factory\MetadataFactoryInterface;

/**
 * Loads metadata from validator metadata.
 *
 * Attributes must be loaded first.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ValidatorMetadataLoader implements LoaderInterface
{
    /**
     * @var string[] A list of constraint classes making the entity required.
     */
    public static $requiredConstraints = [
        'Symfony\Component\Validator\Constraints\NotBlank',
        'Symfony\Component\Validator\Constraints\NotNull',
    ];

    /**
     * @var MetadataFactoryInterface
     */
    private $validatorMetadataFactory;

    public function __construct(MetadataFactoryInterface $validatorMetadataFactory)
    {
        $this->validatorMetadataFactory = $validatorMetadataFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function loadClassMetadata(
        ClassMetadata $classMetadata,
        array $normalizationGroups = null,
        array $denormalizationGroups = null,
        array $validationGroups = null
    ) {
        $validatorClassMetadata = $this->validatorMetadataFactory->getMetadataFor($classMetadata->getName());

        foreach ($classMetadata->getAttributes() as $attributeMetadata) {
            $attributeName = $attributeMetadata->getName();

            foreach ($validatorClassMetadata->getPropertyMetadata($attributeName) as $propertyMetadata) {
                if (null === $validationGroups) {
                    foreach ($propertyMetadata->findConstraints($validatorClassMetadata->getDefaultGroup()) as $constraint) {
                        if ($this->isRequired($constraint)) {
                            $attributeMetadata->setRequired(true);

                            break 2;
                        }
                    }
                } else {
                    foreach ($validationGroups as $validationGroup) {
                        foreach ($propertyMetadata->findConstraints($validationGroup) as $constraint) {
                            if ($this->isRequired($constraint)) {
                                $attributeMetadata->setRequired(true);

                                break 3;
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    /**
     * Is this constraint making the related property required?
     *
     * @param Constraint $constraint
     *
     * @return bool
     */
    private function isRequired(Constraint $constraint)
    {
        foreach (self::$requiredConstraints as $requiredConstraint) {
            if ($constraint instanceof $requiredConstraint) {
                return true;
            }
        }

        return false;
    }
}

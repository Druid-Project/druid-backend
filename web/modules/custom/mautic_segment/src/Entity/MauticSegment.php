<?php

namespace Drupal\mautic_segment\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Mautic Segment entity.
 *
 * @ContentEntityType(
 *   id = "mautic_segment",
 *   label = @Translation("Mautic Segment"),
 *   base_table = "mautic_segment",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "mtc_id",
 *   },
 *   links = {
 *     "canonical" = "/mautic-segment/{mautic_segment}",
 *     "collection" = "/mautic-segment/list"
 *   }
 * )
 */
class MauticSegment extends ContentEntityBase implements EntityChangedInterface
{
    use EntityChangedTrait;

    /**
     * The Mautic Segment ID.
     *
     * @var int
     */
    protected $id;

    /**
     * The MTC ID.
     *
     * @var string
     */
    protected $mtc_id;

    /**
     * {@inheritdoc}
     */
    public static function baseFieldDefinitions(EntityTypeInterface $entity_type)
    {
        $fields = parent::baseFieldDefinitions($entity_type);

        $fields['mtc_id'] = BaseFieldDefinition::create('string')
            ->setLabel(\Drupal::translation()->translate('MTC ID'))
            ->setDescription(\Drupal::translation()->translate('The MTC ID.'))
            ->setSettings([
                'max_length' => 255,
                'text_processing' => 0,
            ])
            ->setRequired(true);

        $fields['changed'] = BaseFieldDefinition::create('changed')
            ->setLabel(\Drupal::translation()->translate('Changed'))
            ->setDescription(\Drupal::translation()->translate('The time that the entity was last edited.'));

        return $fields;
    }
}

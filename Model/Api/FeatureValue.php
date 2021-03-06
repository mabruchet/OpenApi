<?php

namespace OpenApi\Model\Api;

use OpenApi\Model\Api\ModelTrait\translatable;

/**
 * Class FeatureValue
 * @package OpenApi\Model\Api
 * @OA\Schema(
 *     description="A feature value"
 * )
 */
class FeatureValue extends BaseApiModel
{
    use translatable;

    /**
     * @var integer
     * @OA\Property(
     *    type="integer",
     * )
     * @Constraint\NotBlank(groups={"read"})
     */
    protected $id;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return FeatureValue
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }
}
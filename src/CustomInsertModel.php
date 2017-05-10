<?php

namespace LaravelCustomInsertModel;

use Illuminate\Database\Eloquent\Model;

class CustomInsertModel extends Model
{
    /**
     * Incrementing property, should be false for custom-insert models.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * For custom-inserts, tail zero digits to be concatenated to the prefix.
     *
     * @var string
     */
    public $tail;

    /**
     * Return the next insert id.
     *
     * @throws \Exception
     * @return int|mixed
     */
    public function nextInsertId()
    {
        $this->validateCustomInsert();

        $primaryPrefix = $this->primaryKeyPrefix();

        $maxId = static::where($keyName = $this->getKeyName(), 'like', "{$primaryPrefix}%")
            ->where(\DB::raw("char_length({$this->getKeyName()})"), '=', $this->pkCharLength())
            ->max($keyName);
        $maxId = $maxId ?: $primaryPrefix.$this->tail;

        return ++$maxId;
    }

    /**
     * The char_length to be check when retrieving the max value of primary key.
     *
     * @return int|string
     */
    public function pkCharLength()
    {
        return strlen($this->tail) + strlen($this->primaryKeyPrefix());
    }

    /**
     * Validate custom insert on model.
     *
     * @throws \Exception
     * @return void
     */
    public function validateCustomInsert()
    {
        $class = get_class($this);

        if (is_null($this->tail)) {
            throw new \Exception("Property \$tail is not set in custom model {$class}.");
        }

        if ($this->incrementing) {
            throw new \Exception("{$class} incrementing property should be false.");
        }

        if (empty($this->primaryKeyPrefix())) {
            throw new \Exception("Performing custom insert on model {$class} must have a primaryKeyPrefix e.g. branch_id.");
        }
    }

    /**
     * Set the id of model.
     *
     * @param int $id
     * @return $this
     */
    public function setIdAttribute($id)
    {
        $this->attributes = array_merge(['id' => $id], $this->attributes);

        return $this;
    }

    /**
     * Override. Save the model to the database. Use custom-insert for inserts.
     *
     * @param  array  $options
     * @return bool
     */
    public function save(array $options = array())
    {
        if ($this->id === null) {
            $this->id = $this->nextInsertId();
        }

        return parent::save($options);
    }
}

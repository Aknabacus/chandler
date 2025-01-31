<?php

namespace App\Models;

use App\Domains\Contact\ManageDocuments\Events\FileDeleted;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class File extends Model
{
    use HasFactory;

    protected $table = 'files';

    /**
     * Possible type.
     */
    public const TYPE_DOCUMENT = 'document';

    public const TYPE_AVATAR = 'avatar';

    public const TYPE_PHOTO = 'photo';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'vault_id',
        'fileable_id',
        'fileable_type',
        'uuid',
        'original_url',
        'cdn_url',
        'name',
        'mime_type',
        'type',
        'size',
    ];

    /**
     * The event map for the model.
     *
     * @var array
     */
    protected $dispatchesEvents = [
        'deleted' => FileDeleted::class,
    ];

    /**
     * Get the vault associated with the file.
     *
     * @return BelongsTo
     */
    public function vault(): BelongsTo
    {
        return $this->belongsTo(Vault::class);
    }

    /**
     * Get the parent model that matches this file.
     *
     * @return MorphTo
     */
    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }
}

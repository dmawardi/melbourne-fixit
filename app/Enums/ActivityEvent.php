<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum ActivityEvent: string
{
    use HasOptions;

    case Created = 'created';
    case Claimed = 'claimed';
    case Updated = 'updated';
    case StatusChanged = 'status_changed';
    case NotesUpdated = 'notes_updated';
    case FileUploaded = 'file_uploaded';
    case FileUpdated = 'file_updated';
    case FileDeleted = 'file_deleted';
    case MessagePosted = 'message_posted';
    case MessageDeleted = 'message_deleted';
    case VendorAssigned = 'vendor_assigned';
    case VendorUnassigned = 'vendor_unassigned';
    case Deleted = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::Created => 'Request submitted',
            self::Claimed => 'Linked to customer account',
            self::Updated => 'Details updated',
            self::StatusChanged => 'Status changed',
            self::NotesUpdated => 'Notes updated',
            self::FileUploaded => 'File uploaded',
            self::FileUpdated => 'File updated',
            self::FileDeleted => 'File deleted',
            self::MessagePosted => 'Update posted',
            self::MessageDeleted => 'Update deleted',
            self::VendorAssigned => 'Vendor assigned',
            self::VendorUnassigned => 'Vendor removed',
            self::Deleted => 'Request deleted',
        };
    }
}

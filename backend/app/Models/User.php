<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'nip_nis',
        'role',
        'specialization',
        'is_active',
        'class_id',
        'student_id',
        'is_secretary',
        'avatar',
        'avatar_url',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'is_homeroom_teacher',
        'is_secretary',
    ];

    public function getIsHomeroomTeacherAttribute(): bool
    {
        if (!in_array($this->role, ['GURU_PENGAJAR', 'GURU_PEMBIMBING'])) {
            return false;
        }
        return $this->relationLoaded('guidedClass') ? !is_null($this->guidedClass) : $this->guidedClass()->exists();
    }

    public function getIsSecretaryAttribute($value): bool
    {
        if (!in_array($this->role, ['SISWA', 'SEKRETARIS'])) {
            return false;
        }
        if ($value) {
            return true;
        }
        return $this->role === 'SEKRETARIS' || ($this->class_id && SchoolClass::where('id', $this->class_id)->where('secretary_id', $this->id)->exists());
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_secretary' => 'boolean',
        ];
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function guidedClass()
    {
        return $this->hasOne(SchoolClass::class, 'advisor_id');
    }

    public function taughtSubjects()
    {
        return $this->hasMany(Subject::class, 'teacher_id');
    }

    public function taughtClasses()
    {
        return $this->belongsToMany(SchoolClass::class, 'teacher_classes', 'teacher_id', 'class_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class, 'teacher_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function auditLogs()
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    public function leaveRequests()
    {
        return $this->hasMany(LeaveRequest::class, 'student_id');
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function parentUser()
    {
        return $this->hasOne(User::class, 'student_id');
    }
}

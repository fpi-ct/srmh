<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('author_access_code', 50);
            $table->string('author_name');
            $table->enum('author_role', ['admin', 'lecturer', 'student_affairs', 'department_head']);
            $table->text('content');
            $table->foreignId('parent_id')->nullable()->constrained('feedbacks')->nullOnDelete();
            $table->boolean('requires_escalation')->default(false);
            $table->timestamp('escalation_resolved_at')->nullable();
            $table->boolean('is_agreed_duplicate')->default(false);
            $table->timestamps();
            $table->index(['student_id', 'created_at']);
            $table->index(['author_access_code']);
            $table->index(['requires_escalation', 'escalation_resolved_at']);
        });

        Schema::create('feedback_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feedback_id')->constrained('feedbacks')->cascadeOnDelete();
            $table->string('user_access_code', 50);
            $table->string('user_name');
            $table->enum('user_role', ['admin', 'lecturer', 'student_affairs', 'department_head']);
            $table->unique(['feedback_id', 'user_access_code']);
        });

        Schema::create('student_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lecturer_id')->constrained('users')->cascadeOnDelete();
            $table->string('class_name', 50)->index();
            $table->string('subject_code', 20)->index();
            $table->string('subject_name')->nullable();
            $table->unsignedInteger('group_id')->nullable();
            $table->string('faculty', 100)->index();
            $table->string('semester', 50)->index();
            $table->decimal('absence_rate', 5, 2)->nullable();
            $table->string('note')->nullable();
            $table->string('member_code', 50)->nullable()->index();
            $table->unique(
                ['student_id', 'lecturer_id', 'class_name', 'subject_code', 'group_id'],
                'student_class_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_classes');
        Schema::dropIfExists('feedback_reactions');
        Schema::dropIfExists('feedbacks');
    }
};

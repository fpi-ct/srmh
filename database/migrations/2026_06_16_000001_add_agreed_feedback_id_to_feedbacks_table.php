<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->foreignId('agreed_feedback_id')->nullable()->after('parent_id')->constrained('feedbacks')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            $table->dropConstrainedForeignId('agreed_feedback_id');
        });
    }
};

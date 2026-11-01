<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->string('name');
            $table->string('address')->nullable();
            $table->string('phone', 20)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->string('phone', 20)->nullable()->after('email');
            $table->boolean('is_active')->default(true)->after('password');
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku', 50)->unique();
            $table->string('barcode', 50)->nullable()->unique();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('unit', 20)->default('cái');
            $table->decimal('cost_price', 15, 0)->default(0);
            $table->decimal('price', 15, 0);
            $table->unsignedInteger('min_stock')->default(5);
            $table->string('image')->nullable();
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 300)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_published')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['is_active', 'is_published']);
        });

        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(0);
            $table->timestamps();
            $table->unique(['branch_id', 'product_id']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 20); // import, sale, refund, adjust
            $table->integer('quantity'); // + nhập, - xuất
            $table->integer('balance_after');
            $table->nullableMorphs('reference');
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['branch_id', 'product_id', 'created_at']);
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone', 20)->unique();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->unsignedInteger('points')->default(0);
            $table->decimal('total_spent', 15, 0)->default(0);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->foreignId('branch_id')->constrained();
            $table->foreignId('user_id')->constrained(); // thu ngân
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('subtotal', 15, 0);
            $table->decimal('discount', 15, 0)->default(0);
            $table->decimal('total', 15, 0);
            $table->decimal('paid', 15, 0)->default(0);
            $table->string('payment_method', 20)->default('cash'); // cash, transfer, card
            $table->string('status', 20)->default('completed'); // completed, refunded, cancelled
            $table->string('note')->nullable();
            $table->timestamps();
            $table->index(['branch_id', 'status', 'created_at']);
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained();
            $table->string('product_name');
            $table->unsignedInteger('quantity');
            $table->decimal('price', 15, 0);
            $table->decimal('cost_price', 15, 0)->default(0);
            $table->decimal('total', 15, 0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('products');
        Schema::dropIfExists('categories');
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn(['phone', 'is_active']);
        });
        Schema::dropIfExists('branches');
    }
};

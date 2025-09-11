<?php
// Build script for MemoWindow template system
// Run this script to regenerate all pages from templates

echo "🏗️  MemoWindow Build System\n";
echo "==========================\n\n";

// Include the template processor
require_once 'templates/processor.php';

try {
    $processor = new TemplateProcessor();
    
    echo "🔄 Regenerating pages from template...\n";
    
    // Generate app.php
    if ($processor->generateAppPage()) {
        echo "✅ Generated app.php\n";
    } else {
        echo "❌ Failed to generate app.php\n";
    }
    
    // Generate memories.php
    if ($processor->generateMemoriesPage()) {
        echo "✅ Generated memories.php\n";
    } else {
        echo "❌ Failed to generate memories.php\n";
    }
    
    // Generate orders.php
    if ($processor->generateOrdersPage()) {
        echo "✅ Generated orders.php\n";
    } else {
        echo "❌ Failed to generate orders.php\n";
    }
    
    echo "\n🎉 Build complete! All pages have been regenerated from templates.\n";
    echo "\n📝 Template System Benefits:\n";
    echo "   • Consistent styling across all pages\n";
    echo "   • Centralized header and navigation\n";
    echo "   • Easy maintenance and updates\n";
    echo "   • Modular JavaScript architecture\n";
    echo "   • Automatic cache busting\n";
    
} catch (Exception $e) {
    echo "❌ Build failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>

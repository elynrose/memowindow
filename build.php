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
    
    // Generate app.html
    if ($processor->generateAppPage()) {
        echo "✅ Generated app.html\n";
    } else {
        echo "❌ Failed to generate app.html\n";
    }
    
    // Generate memories.html
    if ($processor->generateMemoriesPage()) {
        echo "✅ Generated memories.html\n";
    } else {
        echo "❌ Failed to generate memories.html\n";
    }
    
    // Generate orders.html
    if ($processor->generateOrdersPage()) {
        echo "✅ Generated orders.html\n";
    } else {
        echo "❌ Failed to generate orders.html\n";
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

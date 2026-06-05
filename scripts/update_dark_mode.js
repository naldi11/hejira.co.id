import fs from 'fs';
import path from 'path';

const FILES = [
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/BranchRequests/Index.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/BranchRequests/Show.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/BranchRequests/Create.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/TransferRequests/Index.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/TransferRequests/Show.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/TransferRequests/Create.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/ReturnsToGudang/Index.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/ReturnsToGudang/Show.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/ReturnsToGudang/Create.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/Dashboard.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/Transactions/Index.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/Pos/Index.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/TransferToBranch/Receive.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/TransferToBranch/Index.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/TransferToBranch/Show.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/TransferToBranch/Create.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/Productions/Index.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/Productions/Show.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/Productions/Create.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/Returns/Index.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/Returns/Show.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/Returns/Create.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/Stock/Index.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/Stock/Movements.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/Reports/Mingguan.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/Reports/Bulanan.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/Reports/Pelanggan.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/Reports/Laci.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/Reports/Index.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/Reports/Harian.jsx",
    "/Applications/MAMP/htdocs/hejira.co.id/resources/js/Pages/Hendhys/Pending/Index.jsx",
];

function processClasses(classStr) {
    const tokens = classStr.split(/\s+/).filter(Boolean);
    const updatedTokens = [];
    
    let isCard = false;
    if (tokens.includes('rounded-xl') && (tokens.includes('bg-white') || tokens.includes('bg-white/[0.03]'))) {
        isCard = true;
    }
    
    for (const t of tokens) {
        if (t === 'rounded-xl' && isCard) {
            updatedTokens.push('rounded-2xl');
            continue;
        }
        if (t === 'shadow-sm' && isCard) {
            updatedTokens.push('shadow-theme-xs');
            continue;
        }
        if (t === 'dark:hover:bg-gray-700' || t === 'dark:hover:bg-gray-800') {
            continue;
        }
        updatedTokens.push(t);
    }
    
    const textMappings = {
        'text-gray-850': 'dark:text-white/90',
        'text-gray-800': 'dark:text-white/90',
        'text-gray-900': 'dark:text-white',
        'text-gray-700': 'dark:text-gray-300',
        'text-gray-650': 'dark:text-gray-300',
        'text-gray-750': 'dark:text-gray-300',
        'text-gray-600': 'dark:text-gray-300',
        'text-gray-500': 'dark:text-gray-400',
        'text-gray-400': 'dark:text-gray-500',
        'text-amber-500': 'dark:text-amber-400',
        'text-amber-600': 'dark:text-amber-400',
        'bg-amber-50': 'dark:bg-amber-500/10',
        'bg-green-100': 'dark:bg-green-500/10',
        'text-green-700': 'dark:text-green-400',
        'text-green-600': 'dark:text-green-400',
        'bg-yellow-100': 'dark:bg-yellow-500/10',
        'text-yellow-700': 'dark:text-yellow-400',
        'text-yellow-600': 'dark:text-yellow-400',
        'bg-blue-100': 'dark:bg-blue-500/10',
        'text-blue-700': 'dark:text-blue-400',
        'text-blue-600': 'dark:text-blue-400',
        'bg-red-100': 'dark:bg-red-500/10',
        'text-red-700': 'dark:text-red-400',
        'text-red-650': 'dark:text-red-400',
        'text-red-600': 'dark:text-red-400',
        'bg-red-50': 'dark:bg-red-500/10',
        'text-red-500': 'dark:text-red-400',
        'divide-gray-100': 'dark:divide-gray-800',
        'hover:bg-gray-50': 'dark:hover:bg-white/[0.01]',
        'border-gray-100': 'dark:border-gray-800',
        'border-gray-200': 'dark:border-gray-800',
        'border-gray-250': 'dark:border-gray-800',
        'border-gray-150': 'dark:border-gray-800',
        'bg-gray-50/50': 'dark:bg-white/[0.01]',
        'bg-amber-50/50': 'dark:bg-white/[0.02]',
        'bg-amber-50/40': 'dark:bg-white/[0.02]',
        'bg-gray-50': 'dark:bg-white/[0.02]',
        'border-amber-100': 'dark:border-amber-500/20',
        'border-green-200': 'dark:border-green-900/30',
        'bg-green-50': 'dark:bg-green-950/10',
        'text-green-800': 'dark:text-green-400',
        'bg-white': 'dark:bg-white/[0.03]',
    };
    
    const newAdds = [];
    
    if (tokens.includes('border-gray-300')) {
        newAdds.push('dark:border-gray-700');
        if (!tokens.some(tok => tok.startsWith('bg-'))) {
            newAdds.push('bg-white', 'dark:bg-gray-800');
        } else if (!tokens.includes('bg-white') && !tokens.includes('bg-gray-800')) {
            newAdds.push('dark:bg-gray-800');
        } else {
            newAdds.push('dark:bg-gray-800');
        }
        
        if (!tokens.some(tok => tok.startsWith('text-'))) {
            newAdds.push('text-gray-800', 'dark:text-white');
        } else if (tokens.includes('text-gray-800')) {
            newAdds.push('dark:text-white');
        } else {
            newAdds.push('dark:text-white');
        }
        
        if (!tokens.includes('focus:border-amber-500')) {
            newAdds.push('focus:border-amber-500');
        }
        if (!tokens.includes('focus:ring-amber-500')) {
            newAdds.push('focus:ring-amber-500');
        }
    } else {
        for (const [key, val] of Object.entries(textMappings)) {
            if (tokens.includes(key)) {
                newAdds.push(val);
            }
        }
    }
    
    for (const addT of newAdds) {
        if (!updatedTokens.includes(addT)) {
            updatedTokens.push(addT);
        }
    }
    
    return updatedTokens.join(' ');
}

function processFileContent(content, fpath) {
    // 1. Match className="xxx"
    content = content.replace(/className=(["'])(.*?)\1/g, (match, quote, classes) => {
        if (classes.includes('{') || classes.includes('}') || classes.includes('$')) {
            return match;
        }
        const updated = processClasses(classes);
        return `className=${quote}${updated}${quote}`;
    });
    
    // 2. Target properties with keys like color: or className: in JS objects
    content = content.replace(/(color|className)\s*:\s*(["'])(.*?)\2/g, (match, key, quote, classes) => {
        if (classes.includes('{') || classes.includes('}') || classes.includes('$')) {
            return match;
        }
        const updated = processClasses(classes);
        return `${key}: ${quote}${updated}${quote}`;
    });
    
    // 3. Match conditional classes inside ternaries: ? 'xxx' : 'yyy'
    content = content.replace(/(['"])([a-zA-Z0-9\s:/\-\.\[\]_]+)\1/g, (match, quote, classes) => {
        if (!classes || classes.includes('$') || classes.includes('{')) {
            return match;
        }
        // Avoid rewriting general text or non-class keys
        // Simple heuristic: if it has common tailwind classes
        const t = classes.split(/\s+/).filter(Boolean);
        const tailwindKeywords = ['text-', 'bg-', 'border-', 'rounded-', 'shadow-', 'hover:', 'focus:', 'divide-'];
        const isTailwind = t.some(tok => tailwindKeywords.some(kw => tok.startsWith(kw)));
        if (isTailwind) {
            const updated = processClasses(classes);
            return `${quote}${updated}${quote}`;
        }
        return match;
    });

    // Special extra adjustments for Pos/Index.jsx
    if (fpath.includes('Pos/Index.jsx')) {
        // Adjust product button border & hover specifically
        content = content.replace(
            /border-gray-100\s+dark:border-gray-800\s+bg-white\s+dark:bg-white\/\[0\.03\]\s+hover:border-amber-200/g,
            'border-gray-100 dark:border-gray-850 bg-white dark:bg-white/[0.03] hover:border-amber-200 dark:hover:border-amber-500/30'
        );
        content = content.replace(
            /bg-amber-50\s+text-amber-600\s+transition-colors\s+group-hover:bg-amber-100\s+dark:bg-amber-500\/10\s+dark:text-amber-400/g,
            'bg-amber-50 text-amber-600 transition-colors group-hover:bg-amber-100 dark:bg-amber-500/10 dark:text-amber-400 dark:group-hover:bg-amber-500/20'
        );
    }
    
    return content;
}

function main() {
    console.log("Starting JS-based update of JSX files...");
    for (const fpath of FILES) {
        if (!fs.existsSync(fpath)) {
            console.warn(`Warning: File ${fpath} does not exist!`);
            continue;
        }
        
        console.log(`Processing: ${path.basename(fpath)}`);
        const content = fs.readFileSync(fpath, 'utf8');
        const updated = processFileContent(content, fpath);
        fs.writeFileSync(fpath, updated, 'utf8');
    }
    console.log("Finished updating files successfully!");
}

main();

import fs from 'fs'
import path, {resolve} from 'node:path';
import {defineConfig} from 'vite'
import react from '@vitejs/plugin-react-swc'
import tsconfigPaths from 'vite-tsconfig-paths'

function getEntries(srcDir: string = 'src', skipDirs: string[] = []): string[] {
    const output: string[] = [],
        src = resolve(__dirname, srcDir),
        paths = scanTsxFiles(src, 1),
        len = src.toString().length

    for (const p of paths) {
        const relpath = p.substring(len - 3),
            skip = skipDirs.reduce((accum, skipDir) => accum || relpath.startsWith(skipDir + '/'), false)
        if (!skip) {
            output.push(relpath)
        }
    }

    return output
}

function scanTsxFiles(dir: fs.PathLike, maxDepth: number, depth: number = 0): string[] {
    let output: string[] = []
    const files = fs.readdirSync(dir)
    for (const file of files) {
        const filePath = path.join(dir.toString(), file),
            st = fs.statSync(filePath)
        if (st.isDirectory() && depth < maxDepth) {
            output = [...output, ...scanTsxFiles(filePath, maxDepth, depth + 1)]
        } else if (st.isFile() && filePath.endsWith('.tsx')) {
            output.push(filePath)
        }
    }
    return output
}

// https://vitejs.dev/config/
export default defineConfig({
    build: {
        assetsDir: '.',
        emptyOutDir: true,
        manifest: true,
        outDir: './build',
        rollupOptions: {
            input: getEntries(),
        },
        sourcemap: true,
    },
    plugins: [
        react(),
        tsconfigPaths(),
    ],
    publicDir: false,
})

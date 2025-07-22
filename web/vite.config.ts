import { defineConfig } from './.vite/config';
import { readFileSync } from 'fs';
import path from 'path';


// 读取环境变量文件
function loadEnv() {
  const envPath = path.resolve(process.cwd(), '.env.development');
  try {
    const content = readFileSync(envPath, 'utf-8');
    return content.split('\n').reduce((acc, line) => {
      const [key, value] = line.split('=');
      if (key && value) {
        acc[key.trim()] = value.trim().replace(/['"]/g, '');
      }
      return acc;
    }, {} as Record<string, string>);
  } catch (e) {
    console.warn(`无法读取 .env.development 文件: ${e.message}`);
    return {};
  }
}


const env = loadEnv();
export default defineConfig(async () => {
  return {
    application: {},
    vite: {
      build: {
        rollupOptions: {
          output: {
            chunkFileNames: 'assets/js/[name]-[hash].js',
            entryFileNames: 'assets/js/[name]-[hash].js',
            assetFileNames: (assetInfo) => {
              const ext = assetInfo.name ? assetInfo.name.split('.').pop()?.toLowerCase() : '';
              if (!ext) {
                return 'assets/[name]-[hash][extname]';
              }
              if (['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'avif'].includes(ext)) {
                return 'assets/images/[name]-[hash][extname]';
              }
              if (['woff', 'woff2', 'eot', 'ttf', 'otf'].includes(ext)) {
                return 'assets/fonts/[name]-[hash][extname]';
              }
              if (ext === 'css') {
                return 'assets/css/[name]-[hash][extname]';
              }
              return 'assets/[name]-[hash][extname]';
            }
          }
        }
      },
      server: {
        proxy: {
          '/api': {
            changeOrigin: true,
            rewrite: (path) => path.replace(/^\/api/, ''),
            target: env.VITE_AXIOS_BASE_URL || 'http://127.0.0.1:8899',
            ws: true
          }
        },
        '/ws': {
          target: env.VITE_GLOB_WSS_URL || 'ws://127.0.0.1:3898',
          changeOrigin: true,
          ws: true,
          rewrite: (path: string) => path.replace(/^\/ws/, '')
        }
      },
    }
  }
});




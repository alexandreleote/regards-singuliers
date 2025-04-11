const Encore = require('@symfony/webpack-encore');
const ImageminPlugin = require('imagemin-webpack-plugin').default;
const imageminMozjpeg = require('imagemin-mozjpeg');
const imageminPngquant = require('imagemin-pngquant');
const imageminSvgo = require('imagemin-svgo');
const TerserPlugin = require('terser-webpack-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');

// Manually configure the runtime environment if not already configured yet.
// This's useful when you use tools like `symfony var:export` on your production server or require a specific Node.js version.
if (!Encore.isRuntimeEnvironmentConfigured()) {
    Encore.configureRuntimeEnvironment(process.env.NODE_ENV || 'dev');
}

Encore
    // directory where compiled assets will be stored
    .setOutputPath('public/build/')
    // public path used by the web server to access the output path
    .setPublicPath('/build')
    // only needed for CDN's or subdirectory deploy
    //.setManifestKeyPrefix('build/')

    /*
     * ENTRY CONFIG
     *
     * Each entry will result in one JavaScript file (e.g. app.js)
     * and one CSS file (e.g. app.css) if your JavaScript imports CSS.
     */
    .addEntry('app', './assets/app.js')
    .addEntry('test/contact-bot', './assets/test/contact-bot.js')
    .addEntry('test/registration-bot', './assets/test/registration-bot.js')

    // enables the Symfony UX Stimulus bridge
    .enableStimulusBridge('./assets/controllers.json')

    // When enabled, Webpack "splits" your files into smaller pieces for greater optimization.
    .splitEntryChunks()

    // will require an extra script tag for runtime.js
    // but, you probably want this, unless you're building a single-page app
    .enableSingleRuntimeChunk()

    /*
     * FEATURE CONFIG
     *
     * Enable & configure other features below. For a full
     * list of features, see:
     * https://symfony.com/doc/current/frontend.html#adding-more-features
     */
    .cleanupOutputBeforeBuild()
    .enableBuildNotifications()
    .enableSourceMaps(!Encore.isProduction())
    .enableVersioning(Encore.isProduction())

    // Copy videos from assets (our scripts will handle optimization)
    .copyFiles({
        from: './assets/videos',
        to: 'videos/[path][name].[ext]',
        pattern: /\.(mp4|webm|mov|avi)$/,
        includeSubdirectories: true
    })

    // Copy images from assets (our scripts will handle optimization)
    .copyFiles({
        from: './assets/images',
        to: 'images/[path][name].[ext]',
        pattern: /\.(png|jpg|jpeg|gif|ico|svg|webp)$/,
        includeSubdirectories: true
    })
    
    // Configure image optimization
    .addLoader({
        test: /\.(png|jpe?g|gif|svg|webp)$/i,
        loader: 'image-webpack-loader',
        options: {
            disable: !Encore.isProduction(),
            mozjpeg: {
                progressive: true,
                quality: 75
            },
            optipng: {
                enabled: true,
                optimizationLevel: 7
            },
            pngquant: {
                quality: [0.65, 0.90],
                speed: 4
            },
            gifsicle: {
                interlaced: false,
                optimizationLevel: 3
            },
            webp: {
                quality: 75
            }
        }
    })

    // enables and configure @babel/preset-env polyfills
    .configureBabelPresetEnv((config) => {
        config.useBuiltIns = 'usage';
        config.corejs = '3.23';
    })

    // enables Sass/SCSS support
    .enableSassLoader()

    // uncomment if you use TypeScript
    //.enableTypeScriptLoader()

    // uncomment if you use React
    //.enableReactPreset()

    // uncomment to get integrity="..." attributes on your script & link tags
    // requires WebpackEncoreBundle 1.4 or higher
    //.enableIntegrityHashes(Encore.isProduction())

    // uncomment if you're having problems with a jQuery plugin
    //.autoProvidejQuery()
;

const config = Encore.getWebpackConfig();

// Add Imagemin plugin for additional compression in production
if (Encore.isProduction()) {
    // Configure optimization
    if (!config.optimization) {
        config.optimization = {};
    }
    
    // Configure minimizers
    if (!config.optimization.minimizer) {
        config.optimization.minimizer = [];
    }
    
    // Add TerserPlugin with better options for JS minification
    config.optimization.minimizer.push(
        new TerserPlugin({
            terserOptions: {
                compress: {
                    drop_console: true,
                    drop_debugger: true,
                    pure_funcs: ['console.log', 'console.info', 'console.debug']
                },
                mangle: true,
                output: {
                    comments: false,
                    beautify: false
                }
            },
            extractComments: false
        })
    );
    
    // Add CSS Minimizer for better CSS minification
    config.optimization.minimizer.push(
        new CssMinimizerPlugin({
            minimizerOptions: {
                preset: [
                    'default',
                    {
                        discardComments: { removeAll: true },
                        normalizeWhitespace: true,
                        minifyFontValues: true,
                        minifySelectors: true
                    }
                ]
            }
        })
    );
    
    // Add Imagemin plugin for image compression
    config.plugins.push(
        new ImageminPlugin({
            test: /\.(jpe?g|png|gif|svg)$/i,
            plugins: [
                imageminMozjpeg({
                    quality: 75,
                    progressive: true
                }),
                imageminPngquant({
                    quality: [0.65, 0.90]
                }),
                imageminSvgo({
                    plugins: [{
                        name: 'removeViewBox',
                        active: false
                    }]
                })
            ]
        })
    );
}

module.exports = config; 
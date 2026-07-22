'use strict';

const siteUrl = String(process.env.SITE_URL || 'http://127.0.0.1:8080').replace(/\/+$/, '');

module.exports = {
    ci: {
        collect: {
            url: [
                `${siteUrl}/`,
                `${siteUrl}/about.html`,
            ],
            numberOfRuns: 1,
            settings: {
                formFactor: 'mobile',
                onlyCategories: ['performance', 'accessibility', 'best-practices', 'seo'],
            },
        },
        assert: {
            assertions: {
                'categories:performance': ['warn', { minScore: 0.60 }],
                'categories:accessibility': ['warn', { minScore: 0.85 }],
                'categories:best-practices': ['warn', { minScore: 0.85 }],
                'categories:seo': ['warn', { minScore: 0.90 }],
            },
        },
        upload: {
            target: 'filesystem',
            outputDir: './lighthouse-reports',
            reportFilenamePattern: '%%HOSTNAME%%-%%PATHNAME%%-mobile-%%DATETIME%%.report.%%EXTENSION%%',
        },
    },
};

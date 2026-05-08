import { defineCollection, z } from 'astro:content';
import { glob } from 'astro/loaders';

const sectionSchema = z.object({
  eyebrow: z.string().optional(),
  title: z.string(),
  text: z.string(),
  image: z.string().optional(),
  imageAlt: z.string().optional(),
  buttonLabel: z.string().optional(),
  buttonUrl: z.string().optional(),
  items: z.array(z.string()).optional(),
  stats: z
    .array(
      z.object({
        label: z.string(),
        value: z.string()
      })
    )
    .optional()
});

const ctaFields = {
  ctaTitle: z.string(),
  ctaText: z.string(),
  ctaButtonLabel: z.string(),
  ctaButtonUrl: z.string()
};

const pages = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/pages' }),
  schema: () =>
    z.object({
      title: z.string(),
      metaDescription: z.string(),
      slug: z.string(),
      h1: z.string(),
      heroTitle: z.string(),
      heroSubtitle: z.string(),
      heroImage: z.string().optional(),
      heroImageAlt: z.string().optional(),
      intro: z.string(),
      sections: z.array(sectionSchema),
      published: z.boolean().default(true),
      ...ctaFields
    })
});

const localPages = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/local-pages' }),
  schema: () =>
    z.object({
      title: z.string(),
      metaDescription: z.string(),
      slug: z.string(),
      city: z.string(),
      pageType: z.string(),
      h1: z.string(),
      heroTitle: z.string(),
      heroSubtitle: z.string(),
      heroImage: z.string().optional(),
      heroImageAlt: z.string().optional(),
      intro: z.string(),
      sections: z.array(sectionSchema),
      localAdvantages: z.array(z.string()),
      nearbyCities: z.array(z.string()),
      published: z.boolean().default(true),
      ...ctaFields
    })
});

const blog = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/blog' }),
  schema: () =>
    z.object({
      title: z.string(),
      metaDescription: z.string(),
      slug: z.string(),
      category: z.string(),
      date: z.coerce.date(),
      excerpt: z.string(),
      featuredImage: z.string().optional(),
      featuredImageAlt: z.string().optional(),
      published: z.boolean().default(true)
    })
});

const testimonials = defineCollection({
  loader: glob({ pattern: '**/*.md', base: './src/content/testimonials' }),
  schema: z.object({
    name: z.string(),
    location: z.string(),
    propertyType: z.string(),
    rating: z.number().min(1).max(5).default(5),
    published: z.boolean().default(true)
  })
});

const settings = defineCollection({
  loader: glob({ pattern: '**/*.json', base: './src/content/settings' }),
  schema: z.object({
    siteName: z.string(),
    baseline: z.string(),
    mickaelName: z.string(),
    marionName: z.string(),
    mickaelPhoto: z.string().optional(),
    marionPhoto: z.string().optional(),
    phone: z.string(),
    email: z.string(),
    mainCity: z.string(),
    coveredAreas: z.array(z.string()),
    facebookUrl: z.string().optional(),
    instagramUrl: z.string().optional(),
    iadUrl: z.string().optional(),
    footerText: z.string(),
    mainCtaLabel: z.string(),
    mainCtaUrl: z.string()
  })
});

export const collections = {
  pages,
  'local-pages': localPages,
  blog,
  testimonials,
  settings
};
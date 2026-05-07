import { defineCollection, z } from 'astro:content';

const sectionSchema = z.object({
  eyebrow: z.string().optional(),
  title: z.string(),
  text: z.string(),
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
  type: 'content',
  schema: ({ image }) =>
    z.object({
      title: z.string(),
      metaDescription: z.string(),
      slug: z.string(),
      h1: z.string(),
      heroTitle: z.string(),
      heroSubtitle: z.string(),
      heroImage: z.union([image(), z.string()]).optional(),
      intro: z.string(),
      sections: z.array(sectionSchema),
      ...ctaFields
    })
});

const localPages = defineCollection({
  type: 'content',
  schema: ({ image }) =>
    z.object({
      title: z.string(),
      metaDescription: z.string(),
      slug: z.string(),
      city: z.string(),
      pageType: z.string(),
      h1: z.string(),
      heroTitle: z.string(),
      heroSubtitle: z.string(),
      intro: z.string(),
      sections: z.array(sectionSchema),
      localAdvantages: z.array(z.string()),
      nearbyCities: z.array(z.string()),
      featuredImage: z.union([image(), z.string()]).optional(),
      published: z.boolean().default(true),
      ...ctaFields
    })
});

const blog = defineCollection({
  type: 'content',
  schema: ({ image }) =>
    z.object({
      title: z.string(),
      metaDescription: z.string(),
      slug: z.string(),
      category: z.string(),
      date: z.coerce.date(),
      excerpt: z.string(),
      featuredImage: z.union([image(), z.string()]).optional(),
      published: z.boolean().default(true)
    })
});

const testimonials = defineCollection({
  type: 'content',
  schema: z.object({
    name: z.string(),
    location: z.string(),
    transactionType: z.string(),
    rating: z.number().min(1).max(5).default(5),
    published: z.boolean().default(true)
  })
});

const settings = defineCollection({
  type: 'data',
  schema: z.object({
    siteName: z.string(),
    mickaelName: z.string(),
    marionName: z.string(),
    phone: z.string(),
    email: z.string(),
    primaryCity: z.string(),
    coveredAreas: z.array(z.string()),
    socialLinks: z.object({
      linkedin: z.string().optional(),
      facebook: z.string().optional(),
      instagram: z.string().optional()
    }),
    shortPresentation: z.string(),
    primaryCta: z.object({
      label: z.string(),
      url: z.string()
    }),
    footer: z.object({
      legalName: z.string(),
      legalNotice: z.string(),
      privacyLabel: z.string(),
      privacyUrl: z.string(),
      termsLabel: z.string(),
      termsUrl: z.string(),
      copyright: z.string()
    })
  })
});

export const collections = {
  pages,
  'local-pages': localPages,
  blog,
  testimonials,
  settings
};
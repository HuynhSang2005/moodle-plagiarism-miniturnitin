import { z } from 'zod';

// define schema cho body của request khi submit job
export const submitJobSchema = z.object({
  body: z.object({
    text: z.string()
      .nonempty({ message: 'Text is required' }),
  }),
});
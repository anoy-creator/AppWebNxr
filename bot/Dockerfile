# Use an official Node runtime
FROM node:18-bullseye-slim

# Create app directory
WORKDIR /usr/src/app

# Copy package files first to leverage Docker layer caching
COPY package*.json ./

# Install only production dependencies
RUN npm ci --only=production

# Copy application source
COPY . .

# Set environment
ENV NODE_ENV=production

# Start the bot
CMD ["node", "index.js"]

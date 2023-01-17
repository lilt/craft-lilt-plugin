const generateJobData = function() {
  const date = Date.now()
  const randomValue = Math.floor(Math.random() * 10000)

  const jobHash = `${date}-${randomValue}`

  const jobTitle = `Automation job | ${jobHash}`
  const slug = jobTitle.
      replace(':', '-').
      replace(' | ', '-').
      replace(/ /g, '-').
      toLowerCase()

  return {jobHash, jobTitle, slug}
}

export default { generateJobData }
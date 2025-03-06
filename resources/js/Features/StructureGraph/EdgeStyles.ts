import { MarkerType } from "@vue-flow/core"

export const orangeStyle = {
  style: { stroke: 'peru' },
  labelStyle: { fill: 'peru' },
  markerEnd: {
    type: MarkerType.ArrowClosed,
    color: 'peru'
  },
}

export const redStyle = {
  style: { stroke: 'firebrick' },
  labelStyle: { fill: 'firebrick' },
  markerEnd: {
    type: MarkerType.ArrowClosed,
    color: 'firebrick'
  },
}

export const parentStyle = { backgroundColor: 'rgba(60,30,60, 0.02)', paddingTop: '10px' }
